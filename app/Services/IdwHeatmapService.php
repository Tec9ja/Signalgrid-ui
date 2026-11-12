<?php

namespace App\Services;

use App\Models\Survey;
use App\Support\RfGeometry;
use Illuminate\Validation\ValidationException;

class IdwHeatmapService
{
    public function generate(Survey $survey, array $options = []): array
    {
        $width = (int) ($options['grid_width'] ?? 80);
        $height = (int) ($options['grid_height'] ?? 60);
        $power = (float) ($options['power'] ?? 2);
        $threshold = (float) ($options['weak_threshold'] ?? -75);
        $fraction = (float) ($options['max_distance'] ?? 0.30);

        $survey->loadMissing('floorPlan');
        $scale = RfGeometry::scale($survey->floorPlan);
        $radius = $fraction * max($scale['width'], $scale['height']);

        $rows = $survey->points()
            ->where('validation_status', 'valid')
            ->with(['measurements' => fn ($query) => $query->whereNotNull('rssi_dbm')->where('is_simulated', false)->latest('captured_at')])
            ->get();

        $points = [];
        $ids = [];
        foreach ($rows as $point) {
            $measurement = $point->measurements->first();
            if (! $measurement) {
                continue;
            }
            [$px, $py] = RfGeometry::locate((float) $point->x_normalized, (float) $point->y_normalized, $scale);
            $points[] = [
                'x' => (float) $point->x_normalized,
                'y' => (float) $point->y_normalized,
                'px' => $px,
                'py' => $py,
                'value' => (float) $measurement->rssi_dbm,
                'id' => $measurement->id,
            ];
            $ids[] = $measurement->id;
        }

        $minimum = max(3, (int) $survey->minimum_points);
        if (count($points) < $minimum) {
            throw ValidationException::withMessages([
                'heatmap' => "At least {$minimum} valid non-simulated RSSI measurements are required.",
            ]);
        }

        $boundary = $survey->floorPlan->usable_boundary;
        if (! $boundary || count($boundary) < 3) {
            throw ValidationException::withMessages(['heatmap' => 'A usable floor boundary is required.']);
        }

        $grid = [];
        $confidence = [];
        $covered = 0;
        $usableCells = 0;
        $estimatedCells = 0;

        for ($gy = 0; $gy < $height; $gy++) {
            $gridRow = [];
            $confidenceRow = [];
            $y = $height === 1 ? 0 : $gy / ($height - 1);
            for ($gx = 0; $gx < $width; $gx++) {
                $x = $width === 1 ? 0 : $gx / ($width - 1);
                if (! $this->inside($x, $y, $boundary)) {
                    $gridRow[] = null;
                    $confidenceRow[] = null;
                    continue;
                }
                $usableCells++;
                [$px, $py] = RfGeometry::locate($x, $y, $scale);
                [$value, $distance] = $this->estimate($px, $py, $points, $power);
                if ($distance > $radius) {
                    $gridRow[] = null;
                    $confidenceRow[] = 0;
                    continue;
                }
                $estimatedCells++;
                $rounded = round($value, 2);
                $gridRow[] = $rounded;
                $confidenceRow[] = round(max(0, min(1, 1 - ($distance / $radius))), 3);
                if ($rounded >= $threshold) {
                    $covered++;
                }
            }
            $grid[] = $gridRow;
            $confidence[] = $confidenceRow;
        }

        $goodSignal = $usableCells ? round(($covered / $usableCells) * 100, 3) : null;
        $estimated = $usableCells ? round(($estimatedCells / $usableCells) * 100, 3) : 0;
        $unmeasured = $usableCells ? round((($usableCells - $estimatedCells) / $usableCells) * 100, 3) : null;

        return [
            'grid' => $grid,
            'confidence' => $confidence,
            'source_points' => array_map(fn ($point) => [
                'x' => $point['x'],
                'y' => $point['y'],
                'value' => $point['value'],
                'id' => $point['id'],
            ], $points),
            'source_measurement_ids' => $ids,
            'source_point_count' => count($points),
            'coverage_percent' => $goodSignal,
            'estimated_area_percent' => $estimated,
            'unmeasured_area_percent' => $unmeasured,
            'parameters' => [
                'width' => $width,
                'height' => $height,
                'power' => $power,
                'threshold' => $threshold,
                'maxDistance' => $fraction,
                'distance_unit' => $scale['unit'],
                'calibrated' => $scale['calibrated'],
                'plan_width_metres' => $scale['calibrated'] ? round($scale['width'], 3) : null,
                'plan_height_metres' => $scale['calibrated'] ? round($scale['height'], 3) : null,
                'max_distance_metres' => $scale['calibrated'] ? round($radius, 3) : null,
                'good_signal_coverage_percent' => $goodSignal,
                'estimated_coverage_percent' => $estimated,
                'unmeasured_area_percent' => $unmeasured,
                'coverage_definition' => 'Good-signal coverage is the share of the usable floor at or above the RSSI threshold. Cells without an estimate are unmeasured and are not counted as good coverage.',
                'rssi_bands' => 'Weak at or below -75 dBm. Fair from -74 to -67. Good from -66 to -60. Strong at or above -59.',
                'limitation' => $scale['calibrated']
                    ? null
                    : 'This floor plan has no metre scale. Distances are shares of the image, not metres. Calibrate the plan before treating the heat map as a physical result.',
            ],
        ];
    }

    private function estimate(float $x, float $y, array $points, float $power): array
    {
        $sum = 0;
        $weights = 0;
        $nearest = INF;

        foreach ($points as $point) {
            $distance = hypot($x - $point['px'], $y - $point['py']);
            $nearest = min($nearest, $distance);
            if ($distance < 0.000001) {
                return [$point['value'], 0];
            }
            $weight = 1 / ($distance ** $power);
            $sum += $weight * $point['value'];
            $weights += $weight;
        }

        return [$sum / $weights, $nearest];
    }

    private function inside(float $x, float $y, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $polygon[$i]['x'];
            $yi = (float) $polygon[$i]['y'];
            $xj = (float) $polygon[$j]['x'];
            $yj = (float) $polygon[$j]['y'];
            $cross = (($yi > $y) !== ($yj > $y))
                && ($x < (($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi));
            if ($cross) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
