<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\FloorPlan;
use App\Services\FloorPlanCalibrationService;
use App\Support\PersistentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FloorPlanController extends Controller
{
    public function store(Request $r, Floor $floor): JsonResponse|RedirectResponse
    {
        abort_unless($r->user()->can('locations.manage'), 403);
        $d = $r->validate([
            'floor_plan' => 'required|file|mimetypes:image/png,image/jpeg,image/webp,image/gif,application/pdf|max:20480',
        ], [
            'floor_plan.required' => 'Choose a PNG, JPG, WEBP, GIF, or PDF floor plan.',
            'floor_plan.mimetypes' => 'Use a PNG, JPG, WEBP, GIF, or PDF floor plan.',
            'floor_plan.uploaded' => 'The floor plan did not upload. Use a PNG, JPG, WEBP, GIF, or PDF of 20 MB or smaller.',
            'floor_plan.max' => 'The floor plan must be 20 MB or smaller.',
        ]);

        $f = $d['floor_plan'];
        $bytes = file_get_contents($f->getRealPath());
        if (! is_string($bytes) || $bytes === '') {
            throw ValidationException::withMessages(['floor_plan' => 'The floor plan could not be read.']);
        }

        $dimensions = null;
        if (str_starts_with((string) $f->getMimeType(), 'image/')) {
            $dimensions = @getimagesize($f->getRealPath());
        }

        $disk = PersistentStorage::diskName();
        $path = $f->store("floor-plans/{$floor->id}", $disk);
        $keepInDatabase = $disk !== 's3';
        if ($path === false) {
            if (! $keepInDatabase) {
                throw ValidationException::withMessages(['floor_plan' => 'The floor plan could not be stored.']);
            }
            $path = 'database';
            $disk = 'database';
        }

        $plan = DB::transaction(function () use ($r, $floor, $f, $path, $dimensions, $disk, $bytes, $keepInDatabase) {
            $floor->floorPlans()->where('is_active', true)->update(['is_active' => false]);
            $plan = $floor->floorPlans()->create([
                'uploaded_by' => $r->user()->id,
                'original_name' => $f->getClientOriginalName(),
                'file_path' => $path,
                'storage_disk' => $disk,
                'mime_type' => $f->getMimeType(),
                'file_size' => $f->getSize(),
                'original_width' => $dimensions[0] ?? null,
                'original_height' => $dimensions[1] ?? null,
                'version' => ($floor->floorPlans()->max('version') ?? 0) + 1,
                'is_active' => true,
            ]);
            if ($keepInDatabase) {
                $plan->storedFile()->create(['contents' => base64_encode($bytes)]);
            }

            return $plan->unsetRelation('storedFile');
        });

        if (! $r->expectsJson()) {
            return redirect()->route('floor-plans.workspace', $floor);
        }

        return response()->json($plan, 201);
    }

    public function file(Request $r, FloorPlan $floorPlan): Response
    {
        abort_unless($r->user()->can('dashboard.view'), 403);
        $disk = $floorPlan->storage_disk ?: 'local';
        $headers = [
            'Content-Type' => $floorPlan->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($disk === 's3') {
            abort_unless(Storage::disk('s3')->exists($floorPlan->file_path), 404);

            return Storage::disk('s3')->response($floorPlan->file_path, $floorPlan->original_name, $headers);
        }

        $encoded = $floorPlan->storedFile()->value('contents');
        if (is_string($encoded) && $encoded !== '') {
            $bytes = base64_decode($encoded, true);
            abort_unless(is_string($bytes) && $bytes !== '', 404);
            $name = str_replace(['"', "\r", "\n"], '', basename((string) $floorPlan->original_name));

            return response($bytes, 200, $headers + [
                'Content-Disposition' => 'inline; filename="'.$name.'"',
            ]);
        }

        abort_unless($disk !== 'database' && Storage::disk($disk)->exists($floorPlan->file_path), 404);

        return Storage::disk($disk)->response($floorPlan->file_path, $floorPlan->original_name, $headers);
    }

    public function calibrate(Request $r, FloorPlan $floorPlan, FloorPlanCalibrationService $service): JsonResponse
    {
        abort_unless($r->user()->can('locations.manage'), 403);
        $d = $r->validate([
            'x1' => 'required|numeric|between:0,1',
            'y1' => 'required|numeric|between:0,1',
            'x2' => 'required|numeric|between:0,1',
            'y2' => 'required|numeric|between:0,1',
            'distance_metres' => 'required|numeric|min:0.1|max:10000',
        ]);
        try {
            $plan = $service->calibrate($floorPlan, $d, $r->user()->id);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['calibration' => $e->getMessage()]);
        }

        return response()->json($plan);
    }

    public function boundary(Request $r, FloorPlan $floorPlan): JsonResponse
    {
        abort_unless($r->user()->can('locations.manage'), 403);
        $d = $r->validate([
            'points' => 'required|array|min:3|max:500',
            'points.*.x' => 'required|numeric|between:0,1',
            'points.*.y' => 'required|numeric|between:0,1',
        ]);
        $floorPlan->update(['usable_boundary' => $d['points']]);

        return response()->json($floorPlan->refresh());
    }

    public function activate(Request $r, FloorPlan $floorPlan): JsonResponse
    {
        abort_unless($r->user()->can('locations.manage'), 403);
        DB::transaction(function () use ($floorPlan) {
            $floorPlan->floor->floorPlans()->update(['is_active' => false]);
            $floorPlan->update(['is_active' => true]);
        });

        return response()->json($floorPlan->refresh());
    }

    public function destroy(Request $r, FloorPlan $floorPlan): JsonResponse
    {
        abort_unless($r->user()->can('locations.manage'), 403);
        $disk = $floorPlan->storage_disk ?: 'local';
        if ($disk !== 'database' && $floorPlan->file_path) {
            Storage::disk($disk)->delete($floorPlan->file_path);
        }
        $floorPlan->delete();

        return response()->json(null, 204);
    }
}
