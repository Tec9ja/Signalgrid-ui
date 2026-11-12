<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\Survey;
use App\Models\User;
use App\Services\IdwHeatmapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdwPhysicalDistanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rectangular_plan_uses_metres_instead_of_normalized_distances(): void
    {
        $plain = $this->generate(null, 1);
        $physical = $this->generate(100, 1);

        $this->assertSame('normalized', $plain['parameters']['distance_unit']);
        $this->assertSame('metres', $physical['parameters']['distance_unit']);
        $this->assertEqualsWithDelta(-44.55, $plain['grid'][0][2], 0.05);
        $this->assertEqualsWithDelta(-54.29, $physical['grid'][0][2], 0.05);
        $this->assertLessThan($plain['grid'][0][2], $physical['grid'][0][2]);
    }

    public function test_good_signal_coverage_uses_the_whole_usable_floor(): void
    {
        $result = $this->generate(null, 0.05, [
            [0.02, 0.02, -40],
            [0.03, 0.02, -40],
            [0.02, 0.03, -40],
        ]);

        $this->assertLessThan(30, $result['coverage_percent']);
        $this->assertLessThan(30, $result['estimated_area_percent']);
        $this->assertGreaterThan(50, $result['unmeasured_area_percent']);
        $this->assertLessThanOrEqual($result['estimated_area_percent'], $result['coverage_percent']);
        $this->assertStringContainsString('not counted as good coverage', $result['parameters']['coverage_definition']);
    }

    private function generate(?float $pixelsPerMetre, float $maxDistance, array $samples = [[0, 0, -40], [0, 1, -90], [1, 0, -40]]): array
    {
        $user = User::factory()->create();
        $institution = Institution::create(['name' => 'T', 'slug' => 't-'.uniqid()]);
        $campus = $institution->campuses()->create(['name' => 'M', 'code' => 'M']);
        $building = $campus->buildings()->create(['name' => 'B', 'code' => 'B']);
        $floor = $building->floors()->create(['name' => 'G', 'code' => 'G', 'level_number' => 0]);
        $plan = $floor->floorPlans()->create([
            'uploaded_by' => $user->id,
            'original_name' => 'plan.png',
            'file_path' => 'p',
            'mime_type' => 'image/png',
            'file_size' => 1,
            'original_width' => 2000,
            'original_height' => 500,
            'pixels_per_metre' => $pixelsPerMetre,
            'usable_boundary' => [['x' => -0.01, 'y' => -0.01], ['x' => 1.01, 'y' => -0.01], ['x' => 1.01, 'y' => 1.01], ['x' => -0.01, 'y' => 1.01]],
            'version' => 1,
            'is_active' => true,
        ]);
        $survey = Survey::create([
            'floor_id' => $floor->id,
            'floor_plan_id' => $plan->id,
            'created_by' => $user->id,
            'name' => 'Distance',
            'minimum_points' => 3,
        ]);
        foreach ($samples as [$x, $y, $rssi]) {
            $point = $survey->points()->create([
                'floor_plan_id' => $plan->id,
                'captured_by' => $user->id,
                'x_normalized' => $x,
                'y_normalized' => $y,
                'source_type' => 'measured',
                'collection_method' => 'manual',
                'validation_status' => 'valid',
                'captured_at' => now(),
            ]);
            $point->measurements()->create([
                'rssi_dbm' => $rssi,
                'source_type' => 'measured',
                'collection_method' => 'manual',
                'is_simulated' => false,
                'captured_at' => now(),
            ]);
        }

        return app(IdwHeatmapService::class)->generate($survey, [
            'grid_width' => 5,
            'grid_height' => 5,
            'power' => 2,
            'weak_threshold' => -75,
            'max_distance' => $maxDistance,
        ]);
    }
}
