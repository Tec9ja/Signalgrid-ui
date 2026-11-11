<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\Building;
use App\Models\Campus;
use App\Models\FeedbackResponse;
use App\Models\Floor;
use App\Models\FloorPlan;
use App\Models\Institution;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;

class CampusSetupService
{
    public function apply(array $input): array
    {
        return DB::transaction(function () use ($input) {
            $institution = $this->saveInstitution($input['institution']);
            $campus = $this->saveCampus($institution, $input['campus']);
            $buildings = $this->syncBuildings(
                $campus,
                $input['buildings'] ?? [],
                (bool) ($input['retire_unlisted'] ?? false),
            );

            return [
                'institution' => $institution->fresh(),
                'campus' => $campus->fresh(),
                'buildings' => $buildings,
            ];
        });
    }

    public function summary(array $result): string
    {
        $parts = ['Campus setup saved for '.$result['institution']->name.'.'];
        $retired = $result['buildings']['retired'];
        if ($retired !== []) {
            $parts[] = 'Removed placeholders with no survey records: '.implode(', ', $retired).'.';
        }
        foreach ($result['buildings']['kept'] as $kept) {
            $parts[] = $kept['name'].' was kept because it has '.$kept['reason'].'.';
        }

        return implode(' ', $parts);
    }

    public static function blankForm(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'type' => 'university',
            'timezone' => 'Africa/Lagos',
            'website' => '',
            'motto' => '',
            'campus_name' => '',
            'campus_code' => '',
            'address' => '',
            'locality' => '',
            'country' => '',
            'latitude' => '',
            'longitude' => '',
            'reference_source' => 'administrator',
            'setup_note' => '',
            'buildings' => [
                ['name' => '', 'code' => '', 'description' => ''],
            ],
        ];
    }

    public function formFromWorkspace(?Institution $institution): array
    {
        if (! $institution) {
            return self::blankForm();
        }

        $campus = $institution->campuses->sortBy('id')->first();
        $form = self::blankForm();
        $form['name'] = $institution->name;
        $form['slug'] = $institution->slug;
        $form['type'] = $institution->type ?: 'university';
        $form['timezone'] = $institution->timezone ?: 'Africa/Lagos';
        $form['website'] = $institution->website ?? '';
        $form['motto'] = $institution->motto ?? '';

        if (! $campus) {
            return $form;
        }

        $form['campus_name'] = $campus->name;
        $form['campus_code'] = $campus->code;
        $form['address'] = $campus->address ?? '';
        $form['locality'] = $campus->locality ?? '';
        $form['country'] = $campus->country ?? '';
        $form['latitude'] = $this->coordinate($campus->latitude);
        $form['longitude'] = $this->coordinate($campus->longitude);
        $form['reference_source'] = $campus->reference_source ?: 'administrator';
        $form['setup_note'] = $campus->setup_note ?? '';
        $buildings = $campus->buildings->sortBy([['display_order', 'asc'], ['name', 'asc']])->values();
        if ($buildings->isNotEmpty()) {
            $form['buildings'] = $buildings->map(fn (Building $building) => [
                'name' => $building->name,
                'code' => $building->code,
                'description' => $building->description ?? '',
            ])->all();
        }

        return $form;
    }

    private function saveInstitution(array $input): Institution
    {
        $institution = Institution::query()->orderBy('id')->first();
        $attributes = [
            'name' => $input['name'],
            'slug' => $input['slug'],
            'type' => $input['type'] ?? 'university',
            'timezone' => $input['timezone'] ?? 'Africa/Lagos',
            'website' => $input['website'] ?: null,
            'motto' => $input['motto'] ?: null,
            'status' => 'active',
        ];

        if ($institution) {
            $institution->update($attributes);

            return $institution;
        }

        return Institution::query()->create($attributes);
    }

    private function saveCampus(Institution $institution, array $input): Campus
    {
        $campus = $institution->campuses()->orderBy('id')->first();
        $attributes = [
            'name' => $input['name'],
            'code' => strtoupper($input['code']),
            'address' => $input['address'] ?: null,
            'locality' => $input['locality'] ?: null,
            'country' => $input['country'] ?: null,
            'latitude' => $input['latitude'] !== '' && $input['latitude'] !== null ? $input['latitude'] : null,
            'longitude' => $input['longitude'] !== '' && $input['longitude'] !== null ? $input['longitude'] : null,
            'reference_source' => $input['reference_source'],
            'setup_note' => $input['setup_note'] ?: null,
            'setup_completed_at' => now(),
            'status' => 'active',
        ];

        if ($campus) {
            $campus->update($attributes);

            return $campus;
        }

        return $institution->campuses()->create($attributes);
    }

    private function syncBuildings(Campus $campus, array $buildings, bool $retireUnlisted): array
    {
        $codes = [];
        $saved = [];

        foreach (array_values($buildings) as $index => $row) {
            $code = strtoupper(trim((string) $row['code']));
            $codes[] = $code;
            $attributes = [
                'name' => trim((string) $row['name']),
                'description' => isset($row['description']) && trim((string) $row['description']) !== ''
                    ? trim((string) $row['description'])
                    : null,
                'display_order' => $index,
                'status' => 'active',
            ];
            $building = Building::withTrashed()
                ->where('campus_id', $campus->id)
                ->where('code', $code)
                ->first();

            if ($building) {
                if ($building->trashed()) {
                    $building->restore();
                }
                $building->update($attributes);
            } else {
                $building = $campus->buildings()->create($attributes + ['code' => $code]);
            }

            $saved[] = $building->name;
        }

        $retired = [];
        $kept = [];
        if ($retireUnlisted) {
            $others = $campus->buildings()->when($codes !== [], fn ($query) => $query->whereNotIn('code', $codes))->get();
            foreach ($others as $building) {
                $reason = $this->retentionReason($building);
                if ($reason !== null) {
                    $kept[] = ['name' => $building->name, 'reason' => $reason];
                    continue;
                }
                $this->retireBuilding($building);
                $retired[] = $building->name;
            }
        }

        return ['saved' => $saved, 'retired' => $retired, 'kept' => $kept];
    }

    private function retentionReason(Building $building): ?string
    {
        $floorIds = Floor::withTrashed()->where('building_id', $building->id)->pluck('id');
        $reasons = [];

        if ($floorIds->isNotEmpty() && FloorPlan::query()->whereIn('floor_id', $floorIds)->exists()) {
            $reasons[] = 'floor plans';
        }
        if ($floorIds->isNotEmpty() && Survey::query()->whereIn('floor_id', $floorIds)->exists()) {
            $reasons[] = 'surveys';
        }
        if ($floorIds->isNotEmpty() && AccessPoint::query()->whereIn('floor_id', $floorIds)->exists()) {
            $reasons[] = 'access points';
        }

        $hasFeedback = FeedbackResponse::query()->where(function ($query) use ($building, $floorIds) {
            $query->where('building_id', $building->id);
            if ($floorIds->isNotEmpty()) {
                $query->orWhereIn('floor_id', $floorIds);
            }
        })->exists();
        if ($hasFeedback) {
            $reasons[] = 'feedback';
        }

        return $reasons === [] ? null : implode(', ', $reasons);
    }

    private function retireBuilding(Building $building): void
    {
        foreach ($building->floors()->get() as $floor) {
            $floor->surveyAreas()->delete();
            $floor->delete();
        }
        $building->delete();
    }

    private function coordinate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 7, '.', ''), '0'), '.');
    }
}
