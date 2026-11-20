<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_viewer_sidebar_is_grouped_and_hides_admin_links(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Viewer');

        $dashboard = $this->sidebar($this->actingAs($user)->get('/dashboard')->assertOk()->getContent(), 'sidebar');
        $locations = $this->sidebar($this->actingAs($user)->get('/locations')->assertOk()->getContent(), 'sgAppSidebar');

        foreach ([$dashboard, $locations] as $nav) {
            $this->assertStringContainsString('Network', $nav);
            $this->assertStringContainsString('Surveys', $nav);
            $this->assertStringContainsString('Reports', $nav);
            $this->assertStringContainsString('Workspace', $nav);
            $this->assertStringContainsString('Project alignment', $nav);
            $this->assertStringContainsString('Settings', $nav);
            $this->assertStringContainsString('Overview', $nav);
            $this->assertStringContainsString('RF survey studio', $nav);
            $this->assertStringNotContainsString('Campus setup', $nav);
            $this->assertStringNotContainsString('Validation', $nav);
            $this->assertStringNotContainsString('Administration', $nav);
            $this->assertLessThan(strpos($nav, 'Workspace'), strpos($nav, 'Reports'));
        }
    }

    public function test_super_admin_sidebar_includes_management_links(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $nav = $this->sidebar($this->actingAs($user)->get('/reports')->assertOk()->getContent(), 'sgAppSidebar');

        $this->assertStringContainsString('Campus setup', $nav);
        $this->assertStringContainsString('Validation', $nav);
        $this->assertStringContainsString('Administration', $nav);
        $this->assertStringContainsString('class="active"', $nav);
        $this->assertLessThan(strpos($nav, 'Reports'), strpos($nav, 'Network'));
        $this->assertLessThan(strpos($nav, '>Admin<'), strpos($nav, 'Workspace'));
    }

    public function test_sidebar_administration_section_follows_each_role(): void
    {
        $surveyor = User::factory()->create(['status' => 'active']);
        $surveyor->assignRole('Surveyor');
        $surveyorNav = $this->sidebar($this->actingAs($surveyor)->get('/dashboard')->assertOk()->getContent(), 'sidebar');
        $this->assertStringContainsString('>Admin<', $surveyorNav);
        $this->assertStringContainsString('Campus setup', $surveyorNav);
        $this->assertStringNotContainsString('Validation', $surveyorNav);
        $this->assertStringNotContainsString('<span>Administration</span>', $surveyorNav);

        $analyst = User::factory()->create(['status' => 'active']);
        $analyst->assignRole('Analyst');
        $analystNav = $this->sidebar($this->actingAs($analyst)->get('/locations')->assertOk()->getContent(), 'sgAppSidebar');
        $this->assertStringContainsString('Workspace', $analystNav);
        $this->assertStringNotContainsString('Campus setup', $analystNav);
        $this->assertStringNotContainsString('Validation', $analystNav);
        $this->assertStringNotContainsString('Administration', $analystNav);

        $network = User::factory()->create(['status' => 'active']);
        $network->assignRole('Network Admin');
        $networkNav = $this->sidebar($this->actingAs($network)->get('/settings')->assertOk()->getContent(), 'sgAppSidebar');
        $this->assertStringContainsString('>Admin<', $networkNav);
        $this->assertStringContainsString('Campus setup', $networkNav);
        $this->assertStringContainsString('Validation', $networkNav);
        $this->assertStringNotContainsString('<span>Administration</span>', $networkNav);
        $this->assertStringContainsString('class="active"', $networkNav);
    }

    private function sidebar(string $html, string $id): string
    {
        $start = strpos($html, 'id="'.$id.'"');
        $this->assertNotFalse($start);
        $end = strpos($html, '</aside>', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
