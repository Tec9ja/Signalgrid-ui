<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_update_user_and_audit_is_recorded(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$user->id}", ['status' => 'inactive', 'role' => 'Viewer'])
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->assertDatabaseHas('audit_logs', ['event' => 'user.updated', 'auditable_id' => $user->id]);
    }

    public function test_super_admin_administration_page_loads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Save');
    }

    public function test_admin_health_checks_are_persisted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'checks', 'checked_at']);

        $this->assertDatabaseHas('system_checks', ['check_key' => 'database', 'status' => 'ok']);
    }

    public function test_viewer_cannot_open_administration(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');
        $this->actingAs($viewer)->get('/admin')->assertForbidden();
    }
}
