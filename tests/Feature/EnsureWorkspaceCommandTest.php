<?php

namespace Tests\Feature;

use App\Models\Floor;
use App\Models\InsightRule;
use App\Models\Institution;
use App\Models\ProjectProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnsureWorkspaceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_starts_from_the_public_record_without_invented_floors(): void
    {
        $this->assertTrue(blank(config('signalgrid.admin_email')));
        $this->assertTrue(blank(config('signalgrid.admin_password')));

        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();
        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();

        $this->assertSame(0, User::query()->count());
        $this->assertSame(1, Institution::query()->count());

        $institution = Institution::query()->firstOrFail();
        $this->assertSame('Adekunle Ajasin University, Akungba', $institution->name);
        $this->assertSame('aaua-akungba', $institution->slug);
        $this->assertSame('https://www.aaua.edu.ng', $institution->website);
        $this->assertSame('For Learning and Service', $institution->motto);

        $campus = $institution->campuses()->firstOrFail();
        $this->assertSame('Akungba Campus', $campus->name);
        $this->assertSame('AAUA', $campus->code);
        $this->assertEqualsWithDelta(7.479234, (float) $campus->latitude, 0.0000001);
        $this->assertEqualsWithDelta(5.748411, (float) $campus->longitude, 0.0000001);
        $this->assertEqualsCanonicalizing(
            ['SENATE', 'LIBRARY', 'PG'],
            $campus->buildings()->pluck('code')->all(),
        );
        $this->assertSame(0, Floor::query()->count());
        $this->assertSame(1, ProjectProfile::query()->count());
        $this->assertTrue(Role::query()->where('name', 'Viewer')->exists());
        $this->assertSame(3, InsightRule::query()->whereIn('code', ['RF-001', 'PERF-001', 'UX-001'])->count());
    }

    public function test_a_second_run_keeps_a_campus_the_students_renamed(): void
    {
        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();

        $institution = Institution::query()->firstOrFail();
        $institution->update([
            'name' => 'Example Polytechnic',
            'slug' => 'example-polytechnic',
        ]);

        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();

        $this->assertSame(1, Institution::query()->count());
        $institution->refresh();
        $this->assertSame('Example Polytechnic', $institution->name);
        $this->assertSame('example-polytechnic', $institution->slug);
        $this->assertSame(1, ProjectProfile::query()->count());
    }

    public function test_admin_is_created_once_and_the_password_is_not_reset(): void
    {
        config([
            'signalgrid.admin_name' => 'Workspace Admin',
            'signalgrid.admin_email' => 'workspace-admin@example.test',
            'signalgrid.admin_password' => 'WorkspaceAdmin!2026',
        ]);

        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();

        $user = User::query()->where('email', 'workspace-admin@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue(Hash::check('WorkspaceAdmin!2026', $user->password));
        $hash = $user->password;

        config(['signalgrid.admin_password' => 'Replacement!2026']);

        $this->artisan('signalgrid:ensure-workspace')->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $user->refresh();
        $this->assertSame($hash, $user->password);
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue(Hash::check('WorkspaceAdmin!2026', $user->password));
        $this->assertFalse(Hash::check('Replacement!2026', $user->password));
    }
}
