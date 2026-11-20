<?php

namespace Tests\Feature;

use App\Models\Floor;
use App\Models\FloorPlanFile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloorPlanUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_uploaded_image_is_kept_in_the_database_and_can_be_opened_after_the_disk_copy_is_gone(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $floor = $this->floor();
        $upload = UploadedFile::fake()->image('ground-floor.png', 640, 480);

        $planId = $this->actingAs($user)->postJson("/api/floors/{$floor->id}/floor-plans", [
            'floor_plan' => $upload,
        ])->assertCreated()
            ->assertJsonPath('storage_disk', 'local')
            ->assertJsonPath('original_width', 640)
            ->json('id');

        $stored = FloorPlanFile::query()->where('floor_plan_id', $planId)->first();
        $this->assertNotNull($stored);
        $this->assertNotEmpty($stored->contents);

        $plan = $floor->floorPlans()->first();
        Storage::disk('local')->delete($plan->file_path);

        $this->actingAs($user)->get(route('floor-plans.file', $planId))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_a_normal_form_post_returns_to_the_floor_plan_page(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->assignRole('Surveyor');
        $floor = $this->floor();

        $this->actingAs($user)->post("/api/floors/{$floor->id}/floor-plans", [
            'floor_plan' => UploadedFile::fake()->image('library.jpg', 80, 60),
        ])->assertRedirect(route('floor-plans.workspace', $floor));

        $this->assertDatabaseCount('floor_plan_files', 1);
    }

    public function test_the_floor_plan_page_can_post_an_image_without_javascript(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $floor = $this->floor();

        $this->actingAs($user)->get(route('floor-plans.workspace', $floor))
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="floor_plan"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('floorPlanConfig', false)
            ->assertSee('PNG, JPG, WEBP, GIF or PDF', false);
    }

    public function test_a_file_that_is_not_an_image_or_pdf_is_refused(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $floor = $this->floor();

        $this->actingAs($user)->postJson("/api/floors/{$floor->id}/floor-plans", [
            'floor_plan' => UploadedFile::fake()->create('notes.txt', 4, 'text/plain'),
        ])->assertUnprocessable()
            ->assertJsonPath('errors.floor_plan.0', 'Use a PNG, JPG, WEBP, GIF, or PDF floor plan.');

        $this->assertDatabaseCount('floor_plans', 0);
    }

    public function test_object_storage_does_not_copy_the_image_into_the_database(): void
    {
        config([
            'filesystems.disks.s3.bucket' => 'signalgrid-test',
            'filesystems.disks.s3.key' => 'test-key',
        ]);
        Storage::fake('s3');
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $floor = $this->floor();

        $this->actingAs($user)->postJson("/api/floors/{$floor->id}/floor-plans", [
            'floor_plan' => UploadedFile::fake()->image('plan.png', 100, 80),
        ])->assertCreated()->assertJsonPath('storage_disk', 's3');

        $this->assertDatabaseCount('floor_plan_files', 0);
        Storage::disk('s3')->assertExists($floor->floorPlans()->first()->file_path);
    }

    private function floor(): Floor
    {
        $institution = \App\Models\Institution::create(['name' => 'Test', 'slug' => 'upload-'.uniqid()]);
        $campus = $institution->campuses()->create(['name' => 'Main', 'code' => 'MAIN']);
        $building = $campus->buildings()->create(['name' => 'Library', 'code' => 'LIB']);

        return $building->floors()->create(['name' => 'Ground', 'code' => 'G', 'level_number' => 0]);
    }
}
