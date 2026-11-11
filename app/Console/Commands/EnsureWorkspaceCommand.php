<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\ProjectProfile;
use Database\Seeders\ApLoadThresholdSeeder;
use Database\Seeders\InsightRuleSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\ProjectAlignmentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemThresholdSeeder;
use Illuminate\Console\Command;

class EnsureWorkspaceCommand extends Command
{
    protected $signature = 'signalgrid:ensure-workspace';

    protected $description = 'Create roles, the campus record, and research rules when they are missing';

    public function handle(): int
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        if (Institution::query()->doesntExist()) {
            $this->seed(LocationSeeder::class);
        }

        $this->seed(SystemThresholdSeeder::class);
        $this->seed(ApLoadThresholdSeeder::class);
        $this->seed(InsightRuleSeeder::class);

        if (ProjectProfile::query()->doesntExist()) {
            $this->seed(ProjectAlignmentSeeder::class);
        }

        $this->info('SignalGrid workspace is ready.');

        return self::SUCCESS;
    }

    private function seed(string $class): void
    {
        $this->call('db:seed', [
            '--class' => $class,
            '--force' => true,
            '--no-interaction' => true,
        ]);
    }
}
