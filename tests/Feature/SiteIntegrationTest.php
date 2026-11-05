<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\{ProjectAlignmentSeeder, RolesAndPermissionsSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, ProjectAlignmentSeeder::class]);
    }

    public function test_guest_entry_points_are_connected(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->get('/feedback')->assertOk()->assertSee('CAMPUS WI-FI FEEDBACK');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_viewer_can_reach_all_read_only_primary_pages(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        foreach ([
            '/dashboard', '/locations', '/surveys', '/access-points', '/insights',
            '/admin/feedback', '/comparisons', '/reports', '/project-alignment',
        ] as $uri) {
            $this->actingAs($viewer)->get($uri)->assertOk();
        }
    }

    public function test_global_navigation_exposes_connected_primary_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/locations')
            ->assertOk()
            ->assertSee(route('dashboard'))
            ->assertSee(route('surveys.index'))
            ->assertSee(route('access-points.index'))
            ->assertSee(route('insights.index'))
            ->assertSee(route('reports.index'))
            ->assertSee(route('validation.index'))
            ->assertSee(route('admin.index'));
    }

    public function test_viewer_is_blocked_from_administration_and_validation_management(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');
        $this->actingAs($viewer)->get('/admin')->assertForbidden();
        $this->actingAs($viewer)->get('/validation')->assertForbidden();
    }

    public function test_legacy_registration_route_is_not_referenced_by_welcome_view(): void
    {
        $view = file_get_contents(resource_path('views/welcome.blade.php'));
        $this->assertStringNotContainsString("route('register')", $view);
    }
}
