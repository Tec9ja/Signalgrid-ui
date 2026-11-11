<?php

namespace App\Support;

class MapTile
{
    public static function from(mixed $latitude, mixed $longitude, int $zoom = 16): ?array
    {
        if ($latitude === null || $latitude === '' || $longitude === null || $longitude === '') {
            return null;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        if ($lat < -85.0 || $lat > 85.0 || $lng < -180.0 || $lng > 180.0) {
            return null;
        }

        $n = 2 ** $zoom;
        $xFloat = ($lng + 180.0) / 360.0 * $n;
        $latRad = deg2rad($lat);
        $yFloat = (1.0 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2.0 * $n;

        return [
            'zoom' => $zoom,
            'x' => (int) floor($xFloat),
            'y' => (int) floor($yFloat),
            'pin_x' => round(($xFloat - floor($xFloat)) * 100, 2),
            'pin_y' => round(($yFloat - floor($yFloat)) * 100, 2),
        ];
    }
}
