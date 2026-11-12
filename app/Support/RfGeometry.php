<?php

namespace App\Support;

use App\Models\FloorPlan;

class RfGeometry
{
    public static function scale(?FloorPlan $plan): array
    {
        $pixelsPerMetre = (float) ($plan->pixels_per_metre ?? 0);
        $width = (int) ($plan->original_width ?? 0);
        $height = (int) ($plan->original_height ?? 0);

        if ($pixelsPerMetre > 0 && $width > 0 && $height > 0) {
            return [
                'calibrated' => true,
                'unit' => 'metres',
                'width' => $width / $pixelsPerMetre,
                'height' => $height / $pixelsPerMetre,
            ];
        }

        return [
            'calibrated' => false,
            'unit' => 'normalized',
            'width' => 1.0,
            'height' => 1.0,
        ];
    }

    public static function locate(float $x, float $y, array $scale): array
    {
        return [$x * $scale['width'], $y * $scale['height']];
    }
}
