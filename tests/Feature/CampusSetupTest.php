<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Campus;
use App\Models\FeedbackResponse;
use App\Models\Floor;
use App\Models\Institution;
use App\Models\SurveyArea;
use App\Models\User;
use App\Services\CampusSetupService;
use App\Support\AauaPublicRecord;
use Database\Seeders\LocationSeeder;
use Database\Seeders\ProjectAlignmentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampusSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_network_admin_can_open_campus_setup_and_a_viewer_cannot(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($admin)->get('/campus-setup')->assertOk()->assertSee('Campus record');
        $this->actingAs($viewer)->get('/campus-setup')->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/campus-setup')->assertRedirect('/login');
    }

    public function test_surveyor_can_open_campus_setup(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Surveyor');

        $this->actingAs($user)->get('/campus-setup')->assertOk();
    }

    public function test_public_record_pack_is_not_saved_until_confirmed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');

        $this->actingAs($admin)
            ->get('/campus-setup?pack=aaua')
            ->assertOk()
            ->assertSee('Nothing is saved until you confirm')
            ->assertSee('P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria')
            ->assertSee('Senate Building')
            ->assertSee('7.479234');

        $this->assertSame(0, Institution::query()->count());
    }

    public function test_saving_another_campus_updates_the_single_institution(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $institution = Institution::query()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'type' => 'university',
            'timezone' => 'Africa/Lagos',
        ]);
        $campus = $institution->campuses()->create([
            'name' => 'Old Campus',
            'code' => 'OLD',
            'address' => 'Akoka, Lagos',
        ]);
        $placeholder = $campus->buildings()->create(['name' => 'Main Library', 'code' => 'LIB']);
        $floor = $placeholder->floors()->create(['name' => 'Ground Floor', 'code' => 'G', 'level_number' => 0]);
        $floor->surveyAreas()->create(['name' => 'Reading Hall A', 'code' => 'READ-A', 'area_type' => 'general']);

        $this->actingAs($admin)->post('/campus-setup', [
            'name' => 'Example Polytechnic',
            'slug' => 'example-poly',
            'type' => 'polytechnic',
            'timezone' => 'Africa/Lagos',
            'website' => 'https://example.edu',
            'motto' => 'Learn',
            'campus_name' => 'City Campus',
            'campus_code' => 'CITY',
            'address' => '1 Campus Road',
            'locality' => 'Akure',
            'country' => 'Nigeria',
            'latitude' => '7.250000',
            'longitude' => '5.190000',
            'reference_source' => 'administrator',
            'setup_note' => 'Entered for this workspace.',
            'retire_unlisted' => '1',
            'buildings' => [
                ['name' => 'Admin Block', 'code' => 'ADMIN', 'description' => 'Main administration block'],
            ],
        ])->assertRedirect('/locations');

        $this->assertSame(1, Institution::query()->count());
        $this->assertSame(1, Campus::query()->count());
        $this->assertSame('Example Polytechnic', Institution::query()->first()->name);
        $this->assertSame('1 Campus Road', Campus::query()->first()->address);
        $this->assertNotNull(Building::query()->where('code', 'ADMIN')->first());
        $this->assertNull(Building::query()->where('code', 'LIB')->first());
        $this->assertNotNull(Floor::onlyTrashed()->where('code', 'G')->first());
        $this->assertNull(SurveyArea::query()->where('name', 'Reading Hall A')->first());
    }

    public function test_reference_pack_sets_aaua_facts_and_keeps_recorded_placeholders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $institution = Institution::query()->create([
            'name' => AauaPublicRecord::NAME,
            'slug' => AauaPublicRecord::SLUG,
            'type' => 'university',
            'timezone' => 'Africa/Lagos',
        ]);
        $campus = $institution->campuses()->create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'address' => 'Akoka, Lagos',
        ]);
        $recorded = $campus->buildings()->create(['name' => 'Main Library', 'code' => 'LIB']);
        $recordedFloor = $recorded->floors()->create(['name' => 'Ground Floor', 'code' => 'G', 'level_number' => 0]);
        $recordedFloor->floorPlans()->create([
            'original_name' => 'plan.png',
            'file_path' => 'plans/plan.png',
            'mime_type' => 'image/png',
            'file_size' => 10,
            'version' => 1,
            'is_active' => true,
        ]);
        $empty = $campus->buildings()->create(['name' => 'Science Complex', 'code' => 'SCI']);
        $emptyFloor = $empty->floors()->create(['name' => 'Ground Floor', 'code' => 'G', 'level_number' => 0]);
        $emptyFloor->surveyAreas()->create(['name' => 'Laboratory Wing', 'code' => 'LAB', 'area_type' => 'general']);
        $withFeedback = $campus->buildings()->create(['name' => 'Student Centre', 'code' => 'STC']);
        FeedbackResponse::query()->create([
            'public_id' => (string) Str::uuid(),
            'campus_id' => $campus->id,
            'building_id' => $withFeedback->id,
            'problem_type' => 'slow_connection',
            'connection_stability' => 'stable',
            'speed_satisfaction' => 3,
            'overall_satisfaction' => 3,
            'activity_type' => 'web_browsing',
            'experienced_at' => now(),
            'submitted_at' => now(),
        ]);

        $form = AauaPublicRecord::formState();
        $this->actingAs($admin)->post('/campus-setup', $form + [
            'campus_name' => $form['campus_name'],
            'retire_unlisted' => '1',
        ])->assertRedirect('/locations');

        $campus->refresh();
        $this->assertSame('P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria', $campus->address);
        $this->assertSame('AAUA', $campus->code);
        $this->assertEqualsWithDelta(7.479234, (float) $campus->latitude, 0.0000001);
        $this->assertEqualsWithDelta(5.748411, (float) $campus->longitude, 0.0000001);
        $this->assertSame('https://www.aaua.edu.ng', $institution->fresh()->website);
        $this->assertNotNull(Building::query()->where('name', 'Senate Building')->first());
        $this->assertSame(0, Floor::query()->whereHas('building', fn ($query) => $query->where('code', 'SENATE'))->count());
        $this->assertNotNull(Building::query()->where('code', 'LIB')->first());
        $this->assertNotNull(Building::query()->where('code', 'STC')->first());
        $this->assertNull(Building::query()->where('code', 'SCI')->first());
        $this->assertNull(Floor::query()->where('building_id', $empty->id)->first());
        $this->assertNull(SurveyArea::query()->where('name', 'Laboratory Wing')->first());
    }

    public function test_location_seeder_does_not_write_the_lagos_address_or_invented_interiors(): void
    {
        $this->seed(LocationSeeder::class);
        app(CampusSetupService::class)->apply(AauaPublicRecord::payload());

        $this->assertSame(1, Institution::query()->count());
        $this->assertSame(1, Campus::query()->count());
        $this->assertSame(3, Building::query()->count());
        $this->assertSame(0, Floor::query()->count());
        $this->assertDatabaseMissing('campuses', ['address' => 'Akoka, Lagos']);
        $this->assertDatabaseHas('campuses', ['address' => 'P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria']);
        $this->assertDatabaseHas('buildings', ['name' => 'University Library']);
        $this->assertDatabaseMissing('buildings', ['name' => 'Main Library']);
        $this->assertDatabaseMissing('survey_areas', ['name' => 'Reading Hall A']);
    }

    public function test_project_alignment_does_not_overwrite_the_campus_record(): void
    {
        $institution = Institution::query()->create([
            'name' => AauaPublicRecord::NAME,
            'slug' => AauaPublicRecord::SLUG,
            'type' => 'university',
            'timezone' => 'Africa/Lagos',
        ]);
        $campus = $institution->campuses()->create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'address' => 'P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria',
        ]);

        $this->seed(ProjectAlignmentSeeder::class);

        $campus->refresh();
        $this->assertSame('Main Campus', $campus->name);
        $this->assertSame('MAIN', $campus->code);
        $this->assertSame('P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria', $campus->address);
        $this->assertSame(AauaPublicRecord::NAME, $institution->fresh()->name);
    }

    public function test_locations_page_shows_the_public_campus_record(): void
    {
        $this->seed(LocationSeeder::class);
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)->get('/locations')
            ->assertOk()
            ->assertSee('P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria')
            ->assertSee('Senate Building')
            ->assertSee('openstreetmap.org', false)
            ->assertSee('https://www.google.com/maps?q=7.479234,5.748411', false)
            ->assertDontSee('Akoka, Lagos')
            ->assertDontSee('Reading Hall A');
    }

    public function test_coordinate_pair_and_duplicate_building_codes_are_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Network Admin');
        $form = AauaPublicRecord::formState();

        $this->actingAs($admin)->post('/campus-setup', array_merge($form, [
            'latitude' => '91',
            'longitude' => '5.748411',
        ]))->assertSessionHasErrors('latitude');

        $this->actingAs($admin)->post('/campus-setup', array_merge($form, [
            'buildings' => [
                ['name' => 'Senate Building', 'code' => 'SENATE', 'description' => ''],
                ['name' => 'Second Senate', 'code' => 'senate', 'description' => ''],
            ],
        ]))->assertSessionHasErrors('buildings.1.code');
    }
}
