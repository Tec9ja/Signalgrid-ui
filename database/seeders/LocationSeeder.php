<?php

namespace Database\Seeders;

use App\Services\CampusSetupService;
use App\Support\AauaPublicRecord;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        app(CampusSetupService::class)->apply(AauaPublicRecord::payload());
    }
}
