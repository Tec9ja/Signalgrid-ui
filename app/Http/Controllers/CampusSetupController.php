<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Institution;
use App\Services\CampusSetupService;
use App\Support\AauaPublicRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampusSetupController extends Controller
{
    public function show(Request $request, CampusSetupService $setup): View
    {
        abort_unless($request->user()->can('locations.manage'), 403);

        $institution = Institution::query()->with(['campuses.buildings'])->orderBy('id')->first();
        if ($request->query('pack') === 'aaua') {
            $mode = 'pack';
            $form = AauaPublicRecord::formState();
        } elseif ($request->boolean('blank')) {
            $mode = 'blank';
            $form = CampusSetupService::blankForm();
        } else {
            $mode = 'workspace';
            $form = $setup->formFromWorkspace($institution);
        }

        return view('campus-setup.show', compact('institution', 'form', 'mode'));
    }

    public function store(Request $request, CampusSetupService $setup): RedirectResponse
    {
        abort_unless($request->user()->can('locations.manage'), 403);

        $institutionId = Institution::query()->orderBy('id')->value('id');
        $campusId = $institutionId
            ? Campus::query()->where('institution_id', $institutionId)->orderBy('id')->value('id')
            : null;

        $slugRule = Rule::unique('institutions', 'slug');
        $codeRule = Rule::unique('campuses', 'code')->where(fn ($query) => $query->where('institution_id', $institutionId));
        if ($institutionId) {
            $slugRule->ignore($institutionId);
        }
        if ($campusId) {
            $codeRule->ignore($campusId);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'type' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:64'],
            'website' => ['nullable', 'url', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
            'campus_name' => ['required', 'string', 'max:150'],
            'campus_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $codeRule],
            'address' => ['nullable', 'string', 'max:1000'],
            'locality' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'reference_source' => ['required', 'in:public_record,administrator'],
            'setup_note' => ['nullable', 'string', 'max:2000'],
            'buildings' => ['nullable', 'array', 'max:40'],
            'buildings.*.name' => ['nullable', 'string', 'max:150'],
            'buildings.*.code' => ['nullable', 'string', 'max:30'],
            'buildings.*.description' => ['nullable', 'string', 'max:1000'],
            'retire_unlisted' => ['nullable', 'boolean'],
        ]);

        if (($data['latitude'] === null) !== ($data['longitude'] === null)) {
            throw ValidationException::withMessages([
                'latitude' => 'Enter both latitude and longitude, or leave both empty.',
            ]);
        }

        $result = $setup->apply([
            'institution' => [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'type' => $data['type'],
                'timezone' => $data['timezone'],
                'website' => $data['website'] ?? null,
                'motto' => $data['motto'] ?? null,
            ],
            'campus' => [
                'name' => $data['campus_name'],
                'code' => $data['campus_code'],
                'address' => $data['address'] ?? null,
                'locality' => $data['locality'] ?? null,
                'country' => $data['country'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'reference_source' => $data['reference_source'],
                'setup_note' => $data['setup_note'] ?? null,
            ],
            'buildings' => $this->buildings($data['buildings'] ?? []),
            'retire_unlisted' => (bool) ($data['retire_unlisted'] ?? false),
        ]);

        return redirect()->route('locations.index')->with('status', $setup->summary($result));
    }

    private function buildings(array $rows): array
    {
        $buildings = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            $description = trim((string) ($row['description'] ?? ''));
            if ($name === '' && $code === '' && $description === '') {
                continue;
            }
            if ($name === '' || $code === '') {
                throw ValidationException::withMessages([
                    "buildings.$index.name" => 'Each building needs both a name and a code.',
                ]);
            }
            if (! preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $code)) {
                throw ValidationException::withMessages([
                    "buildings.$index.code" => 'Use letters, numbers, hyphens or underscores for the building code.',
                ]);
            }
            if (isset($seen[$code])) {
                throw ValidationException::withMessages([
                    "buildings.$index.code" => 'Building codes must be unique.',
                ]);
            }
            $seen[$code] = true;
            $buildings[] = [
                'name' => $name,
                'code' => $code,
                'description' => $description !== '' ? $description : null,
            ];
        }

        return $buildings;
    }
}
