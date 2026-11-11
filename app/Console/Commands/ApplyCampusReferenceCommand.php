<?php

namespace App\Console\Commands;

use App\Services\CampusSetupService;
use App\Support\AauaPublicRecord;
use Illuminate\Console\Command;

class ApplyCampusReferenceCommand extends Command
{
    protected $signature = 'signalgrid:apply-campus-reference';

    protected $description = 'Apply the published AAUA campus reference to this workspace';

    public function handle(CampusSetupService $setup): int
    {
        $result = $setup->apply(AauaPublicRecord::payload());
        $campus = $result['campus'];

        $this->info($setup->summary($result));
        $this->line($campus->name.' · '.$campus->code);
        $this->line((string) $campus->address);
        if ($campus->latitude !== null && $campus->longitude !== null) {
            $this->line($campus->latitude.', '.$campus->longitude);
        }
        foreach ($result['buildings']['saved'] as $name) {
            $this->line('Building: '.$name);
        }

        return self::SUCCESS;
    }
}
