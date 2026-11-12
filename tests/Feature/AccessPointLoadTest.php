<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\ApLoadThresholdSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemThresholdSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessPointLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, SystemThresholdSeeder::class, ApLoadThresholdSeeder::class]);
    }

    private function context(): array
    {
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $institution = Institution::create(['name' => 'T', 'slug' => 't']);
        $campus = $institution->campuses()->create(['name' => 'M', 'code' => 'M']);
        $building = $campus->buildings()->create(['name' => 'B', 'code' => 'B']);
        $floor = $building->floors()->create(['name' => 'G', 'code' => 'G', 'level_number' => 0]);
        $ap = AccessPoint::create(['floor_id' => $floor->id, 'name' => 'AP-01', 'identifier' => 'AP-01', 'configured_client_limit' => 50]);

        return [$user, $ap];
    }

    public function test_high_load_with_degraded_performance_is_possible_congestion(): void
    {
        [$user, $ap] = $this->context();
        $ap->loadSamples()->create([
            'connected_clients' => 80,
            'channel_utilisation_percent' => 85,
            'latency_ms' => 130,
            'packet_loss_percent' => 4,
            'throughput_mbps' => 7,
            'source_type' => 'controller',
            'sampled_at' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/access-points/{$ap->id}/analysis")
            ->assertOk()
            ->assertJson(['classification' => 'possible_congestion', 'rule' => 'LOAD-001']);
        $this->actingAs($user)->get('/access-points')->assertOk()->assertSee('Possible congestion');
    }

    public function test_missing_telemetry_returns_insufficient_data(): void
    {
        [$user, $ap] = $this->context();

        $this->actingAs($user)->getJson("/api/access-points/{$ap->id}/analysis")
            ->assertOk()
            ->assertJson(['classification' => 'insufficient_data']);
        $this->actingAs($user)->get('/access-points')->assertOk()->assertSee('Insufficient data');
    }

    public function test_samples_without_channel_utilisation_are_insufficient(): void
    {
        [$user, $ap] = $this->context();
        $ap->loadSamples()->create([
            'connected_clients' => 10,
            'latency_ms' => 40,
            'packet_loss_percent' => 0,
            'throughput_mbps' => 40,
            'source_type' => 'controller',
            'sampled_at' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/access-points/{$ap->id}/analysis")
            ->assertOk()
            ->assertJsonPath('classification', 'insufficient_data')
            ->assertJsonPath('limitations.0', 'Channel utilisation is unavailable, so congestion cannot be ruled out.');
    }

    public function test_complete_evidence_below_thresholds_is_no_congestion(): void
    {
        [$user, $ap] = $this->context();
        $ap->loadSamples()->create([
            'connected_clients' => 10,
            'channel_utilisation_percent' => 20,
            'latency_ms' => 40,
            'packet_loss_percent' => 1,
            'throughput_mbps' => 40,
            'source_type' => 'controller',
            'sampled_at' => now(),
        ]);

        $this->actingAs($user)->getJson("/api/access-points/{$ap->id}/analysis")
            ->assertOk()
            ->assertJson(['classification' => 'no_congestion_detected']);
        $this->actingAs($user)->get('/access-points')->assertOk()->assertSee('No congestion detected');
    }
}
