<?php

namespace Tests\Feature;

use App\Models\FloorPlan;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\InsightRuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemThresholdSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptionalFloorPlanSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_set_up_a_building_and_record_readings_without_a_floor_plan(): void
    {
        Storage::fake('local');
        $this->seed([SystemThresholdSeeder::class, InsightRuleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $institution = Institution::create(['name' => 'Test University', 'slug' => 'test-university']);

        $this->actingAs($admin)->get('/locations')
            ->assertOk()
            ->assertSee('A floor plan is optional', false)
            ->assertSee('Add another campus', false);

        $campus = $this->actingAs($admin)->postJson("/api/institutions/{$institution->id}/campuses", [
            'name' => 'Akungba Campus',
            'code' => 'AKG',
        ])->assertCreated()->json('id');

        $building = $this->actingAs($admin)->postJson("/api/campuses/{$campus}/buildings", [
            'name' => 'Library',
            'code' => 'LIB',
        ])->assertCreated()->json('id');

        $floor = $this->actingAs($admin)->postJson("/api/buildings/{$building}/floors", [
            'name' => 'Ground',
            'code' => 'G',
            'level_number' => 0,
        ])->assertCreated()->json('id');

        $this->actingAs($admin)->postJson("/api/floors/{$floor}/survey-areas", [
            'name' => 'Reading room',
            'code' => 'RR',
            'area_type' => 'general',
        ])->assertCreated();

        $this->actingAs($admin)->get('/locations')
            ->assertOk()
            ->assertSee('Floor plan optional', false)
            ->assertSee('Add floor plan', false)
            ->assertSee('<summary>Add floor</summary>', false)
            ->assertSee('Reading room', false);

        $this->actingAs($admin)->get('/access-points')
            ->assertOk()
            ->assertSee('A floor plan is not required.', false);

        $this->actingAs($admin)->postJson('/api/access-points', [
            'floor_id' => $floor,
            'name' => 'Library entrance',
            'identifier' => 'AP-LIB-01',
        ])->assertCreated();

        $this->actingAs($admin)->get('/surveys')
            ->assertOk()
            ->assertSee('No floor plan', false)
            ->assertDontSee('name="floor_plan_id" required', false);

        $survey = $this->actingAs($admin)->postJson('/api/surveys', [
            'floor_id' => $floor,
            'name' => 'Ground baseline',
            'purpose' => 'baseline',
            'minimum_points' => 3,
        ])->assertCreated()->assertJsonPath('floor_plan_id', null)->json('id');

        $this->actingAs($admin)->get("/surveys/{$survey}")
            ->assertOk()
            ->assertSee('No floor plan on this survey', false)
            ->assertSee('Enter the reading below. A map position is not needed.', false)
            ->assertSee('Leave the map columns blank. This survey has no floor plan.', false);

        $this->actingAs($admin)->postJson("/api/surveys/{$survey}/start")->assertCreated();

        for ($n = 0; $n < 10; $n++) {
            $point = $this->actingAs($admin)->postJson("/api/surveys/{$survey}/points", [
                'source_type' => 'measured',
                'collection_method' => 'manual',
            ])->assertCreated()->assertJsonPath('floor_plan_id', null)->json('id');

            $this->assertDatabaseHas('measurement_points', [
                'id' => $point,
                'x_normalized' => null,
                'y_normalized' => null,
            ]);

            $this->actingAs($admin)->postJson("/api/measurement-points/{$point}/measurements", [
                'rssi_dbm' => -80,
                'download_mbps' => 8,
                'source_type' => 'measured',
                'collection_method' => 'manual',
            ])->assertCreated();
        }

        $this->actingAs($admin)->get("/surveys/{$survey}")
            ->assertOk()
            ->assertSee('-80 dBm', false)
            ->assertSee('Valid', false);

        $this->actingAs($admin)->postJson("/api/surveys/{$survey}/insights/generate")->assertCreated();
        $this->assertDatabaseHas('insights', ['survey_id' => $survey]);

        $this->actingAs($admin)->postJson("/api/surveys/{$survey}/complete")
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $followup = $this->actingAs($admin)->postJson('/api/surveys', [
            'floor_id' => $floor,
            'name' => 'Ground follow-up',
            'purpose' => 'follow-up',
            'minimum_points' => 3,
        ])->assertCreated()->json('id');
        $followPoint = $this->actingAs($admin)->postJson("/api/surveys/{$followup}/points", [
            'source_type' => 'measured',
            'collection_method' => 'manual',
        ])->assertCreated()->json('id');
        $this->actingAs($admin)->postJson("/api/measurement-points/{$followPoint}/measurements", [
            'rssi_dbm' => -60,
            'source_type' => 'measured',
            'collection_method' => 'manual',
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/comparisons', [
            'baseline_survey_id' => $survey,
            'followup_survey_id' => $followup,
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/reports', [
            'title' => 'Library ground summary',
            'report_type' => 'survey',
            'survey_id' => $survey,
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('snapshot.survey.floor_plan_version', null)
            ->assertJsonFragment(['This survey has no floor plan, so it has no spatial heat map.']);

        $blank = "rssi_dbm,ssid,captured_at\n-67,Campus,2026-11-20T10:00:00+01:00\n";
        $this->actingAs($admin)->postJson("/api/surveys/{$followup}/measurement-imports", [
            'file' => UploadedFile::fake()->createWithContent('readings.csv', $blank),
        ])->assertCreated()->assertJsonPath('accepted_rows', 1)->assertJsonPath('rejected_rows', 0);

        $positioned = "x_normalized,y_normalized,rssi_dbm,captured_at\n0.20,0.30,-66,2026-11-20T11:00:00+01:00\n";
        $this->actingAs($admin)->postJson("/api/surveys/{$followup}/measurement-imports", [
            'file' => UploadedFile::fake()->createWithContent('positioned.csv', $positioned),
        ])->assertCreated()->assertJsonPath('accepted_rows', 0)->assertJsonPath('rejected_rows', 1);

        $this->assertDatabaseMissing('measurement_points', [
            'survey_id' => $followup,
            'x_normalized' => 0.2,
        ]);

        $this->actingAs($admin)->get('/feedback')->assertOk()->assertSee('Ground', false);
    }

    public function test_heat_maps_and_holdout_still_require_a_floor_plan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $institution = Institution::create(['name' => 'Test University', 'slug' => 'test-university']);
        $campus = $institution->campuses()->create(['name' => 'Main', 'code' => 'M']);
        $building = $campus->buildings()->create(['name' => 'Library', 'code' => 'LIB']);
        $floor = $building->floors()->create(['name' => 'Ground', 'code' => 'G', 'level_number' => 0]);
        $survey = $floor->surveys()->create([
            'created_by' => $admin->id,
            'name' => 'No plan',
            'purpose' => 'baseline',
            'minimum_points' => 3,
        ]);

        $this->actingAs($admin)->get("/surveys/{$survey->id}/heatmaps")
            ->assertOk()
            ->assertSee('Add a floor plan before generating a heat map.', false)
            ->assertDontSee('id="generateHeatmap"', false);

        $this->actingAs($admin)->postJson("/api/surveys/{$survey->id}/heatmaps")
            ->assertStatus(422)
            ->assertJsonValidationErrors('heatmap');

        $this->actingAs($admin)->postJson("/api/surveys/{$survey->id}/validation")
            ->assertStatus(422)
            ->assertJsonValidationErrors('validation');
    }

    public function test_a_survey_with_a_floor_plan_still_requires_a_map_position(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $institution = Institution::create(['name' => 'Test University', 'slug' => 'test-university']);
        $campus = $institution->campuses()->create(['name' => 'Main', 'code' => 'M']);
        $building = $campus->buildings()->create(['name' => 'Library', 'code' => 'LIB']);
        $floor = $building->floors()->create(['name' => 'Ground', 'code' => 'G', 'level_number' => 0]);
        $plan = FloorPlan::create([
            'floor_id' => $floor->id,
            'uploaded_by' => $admin->id,
            'original_name' => 'plan.png',
            'file_path' => 'plan.png',
            'mime_type' => 'image/png',
            'file_size' => 1,
            'version' => 1,
            'is_active' => true,
        ]);
        $survey = $floor->surveys()->create([
            'floor_plan_id' => $plan->id,
            'created_by' => $admin->id,
            'name' => 'Mapped',
            'purpose' => 'baseline',
            'minimum_points' => 3,
        ]);

        $this->actingAs($admin)->postJson("/api/surveys/{$survey->id}/points", [
            'source_type' => 'measured',
            'collection_method' => 'manual',
        ])->assertStatus(422)->assertJsonValidationErrors('x_normalized');

        for ($n = 1; $n <= 3; $n++) {
            $point = $this->actingAs($admin)->postJson("/api/surveys/{$survey->id}/points", [
                'x_normalized' => $n / 10,
                'y_normalized' => 0.4,
                'source_type' => 'measured',
                'collection_method' => 'manual',
            ])->assertCreated()->json('id');
            $this->actingAs($admin)->postJson("/api/measurement-points/{$point}/measurements", [
                'rssi_dbm' => -62,
                'source_type' => 'measured',
                'collection_method' => 'manual',
            ])->assertCreated();
        }

        $this->actingAs($admin)->postJson("/api/surveys/{$survey->id}/complete")
            ->assertStatus(422)
            ->assertJsonPath('errors.survey.0', 'The floor plan must be calibrated.');
    }
}
