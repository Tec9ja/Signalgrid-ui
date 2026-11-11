<?php

namespace Tests\Feature;

use App\Models\{Building, Campus, Floor, Institution, User};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_floor_rows_link_to_the_floor_plan_workspace(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $institution = Institution::create(['name' => 'Test University', 'slug' => 'test-university']);
        $campus = Campus::create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
        $building = Building::create(['campus_id' => $campus->id, 'name' => 'Library', 'code' => 'LIB']);
        $floor = Floor::create(['building_id' => $building->id, 'name' => 'Ground', 'code' => 'G', 'level_number' => 0]);

        $this->actingAs($admin)->get('/locations')
            ->assertOk()
            ->assertSee('Open floor plan', false)
            ->assertSee(route('floor-plans.workspace', $floor), false)
            ->assertSee('Save campus', false);
    }

    public function test_survey_index_exposes_workspace_and_heatmap_links(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get('/surveys')
            ->assertOk()
            ->assertSee('id="coverage"', false)
            ->assertSee('id="import-measurements"', false)
            ->assertDontSee('Create survey', false);
    }

    public function test_dashboard_navigation_points_at_live_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee(route('measurements.index'), false)
            ->assertSee(route('settings.index'), false)
            ->assertSee(route('access-points.index'), false)
            ->assertSee(route('insights.index'), false)
            ->assertSee(route('feedback.index'), false)
            ->assertSee('data-go="insights"', false)
            ->assertDontSee('This prototype control is not available yet', false);
    }
}
