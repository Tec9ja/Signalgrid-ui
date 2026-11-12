<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\ProjectProfile;
use App\Models\ReportExport;
use App\Models\Survey;
use App\Models\User;
use App\Support\PersistentStorage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersistentStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_floor_plan_stays_on_local_disk_when_object_storage_is_unset(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->assignRole('Network Admin');
        $floor = $this->floor();

        $this->actingAs($user)->postJson("/api/floors/{$floor->id}/floor-plans", [
            'floor_plan' => UploadedFile::fake()->image('plan.png', 1000, 800),
        ])->assertCreated()->assertJsonPath('storage_disk', 'local');

        $this->assertSame('local', PersistentStorage::diskName());
        Storage::disk('local')->assertExists($floor->floorPlans()->first()->file_path);
    }

    public function test_floor_plan_uses_s3_when_bucket_and_key_are_configured(): void
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
            'floor_plan' => UploadedFile::fake()->image('plan.png', 1000, 800),
        ])->assertCreated()->assertJsonPath('storage_disk', 's3');

        Storage::disk('s3')->assertExists($floor->floorPlans()->first()->file_path);
    }

    public function test_report_export_writes_pdf_and_csv_to_the_configured_disk(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $user->assignRole('Analyst');
        $institution = Institution::create(['name' => 'Adekunle Ajasin University, Akungba', 'slug' => 'aaua-storage']);
        ProjectProfile::create([
            'institution_id' => $institution->id,
            'project_title' => 'Wi-Fi Optimisation',
            'aim' => 'RF heat mapping and load analysis',
            'objectives' => [],
            'scope' => [],
            'limitations' => [],
            'methodology' => [],
            'is_active' => true,
        ]);
        $campus = $institution->campuses()->create(['name' => 'Akungba Campus', 'code' => 'AAUA']);
        $building = $campus->buildings()->create(['name' => 'Library', 'code' => 'LIB']);
        $floor = $building->floors()->create(['name' => 'Ground', 'code' => 'G', 'level_number' => 0]);
        $plan = $floor->floorPlans()->create([
            'uploaded_by' => $user->id,
            'original_name' => 'p',
            'file_path' => 'p',
            'mime_type' => 'image/png',
            'file_size' => 1,
            'version' => 1,
        ]);
        $survey = Survey::create([
            'floor_id' => $floor->id,
            'floor_plan_id' => $plan->id,
            'created_by' => $user->id,
            'name' => 'Baseline',
        ]);
        $id = $this->actingAs($user)->postJson('/api/reports', [
            'title' => 'Baseline Report',
            'report_type' => 'survey',
            'survey_id' => $survey->id,
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->toDateString(),
        ])->assertCreated()->json('id');

        $this->get("/reports/{$id}/csv")->assertOk()->assertSee('Unmeasured area is the share');
        $this->get("/reports/{$id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        $exports = ReportExport::query()->where('report_id', $id)->get();
        $this->assertCount(2, $exports);
        foreach ($exports as $export) {
            $this->assertSame('local', $export->storage_disk);
            $this->assertNotNull($export->file_path);
            Storage::disk('local')->assertExists($export->file_path);
        }
    }

    private function floor()
    {
        $institution = Institution::create(['name' => 'Test', 'slug' => 'storage-'.uniqid()]);
        $campus = $institution->campuses()->create(['name' => 'Main', 'code' => 'MAIN']);
        $building = $campus->buildings()->create(['name' => 'Library', 'code' => 'LIB']);

        return $building->floors()->create(['name' => 'Ground', 'code' => 'G', 'level_number' => 0]);
    }
}
