<?php

namespace Tests\Feature;

use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_dashboard_loads_the_phase_one_interface(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertSee('SignalGrid')
            ->assertSee('Demonstration workspace')
            ->assertSee('data-phase="1"', false);
    }

    public function test_health_endpoint_confirms_database_connectivity(): void
    {
        $this->getJson('/health')->assertOk()->assertJson([
            'status' => 'ok',
            'application' => 'SignalGrid',
            'database' => 'connected',
        ]);
    }
}
