<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\ValidationRun;
use App\Support\RfGeometry;
use Illuminate\Validation\ValidationException;

class ScientificValidationService
{
    public function validate(Survey $survey, int $userId, float $holdoutPercent = 20, float $power = 2): ValidationRun
    {
        $survey->loadMissing('floorPlan');
        if (! $survey->floorPlan) {
            throw ValidationException::withMessages([
                'validation' => 'Add a floor plan before running a spatial holdout.',
            ]);
        }
        $scale = RfGeometry::scale($survey->floorPlan);
        $rows = $survey->points()
            ->where('validation_status', 'valid')
            ->with(['measurements' => fn ($query) => $query->where('is_simulated', false)->whereNotNull('rssi_dbm')->latest('captured_at')])
            ->get();

        $points = [];
        foreach ($rows as $point) {
            $measurement = $point->measurements->first();
            if (! $measurement || $point->x_normalized === null || $point->y_normalized === null) {
                continue;
            }
            [$px, $py] = RfGeometry::locate((float) $point->x_normalized, (float) $point->y_normalized, $scale);
            $x = (float) $point->x_normalized;
            $y = (float) $point->y_normalized;
            $points[] = [
                'id' => $measurement->id,
                'x' => $x,
                'y' => $y,
                'px' => $px,
                'py' => $py,
                'observed' => (float) $measurement->rssi_dbm,
                'bin' => min(2, (int) floor($y * 3)) * 3 + min(2, (int) floor($x * 3)),
            ];
        }

        if (count($points) < 10) {
            throw ValidationException::withMessages([
                'validation' => 'At least 10 valid non-simulated RSSI points are required.',
            ]);
        }

        $holdoutCount = max(2, (int) round(count($points) * ($holdoutPercent / 100)));
        [$holdout, $training] = $this->spatialHoldout($points, $holdoutCount);
        if (count($training) < 3) {
            throw ValidationException::withMessages([
                'validation' => 'At least three training points must remain after holdout selection.',
            ]);
        }

        $predictions = [];
        $errors = [];
        foreach ($holdout as $point) {
            $predicted = $this->estimate($point['px'], $point['py'], $training, $power);
            $error = $predicted - $point['observed'];
            $errors[] = $error;
            $predictions[] = [
                'measurement_id' => $point['id'],
                'x_normalized' => $point['x'],
                'y_normalized' => $point['y'],
                'spatial_bin' => $point['bin'],
                'observed_rssi_dbm' => $point['observed'],
                'predicted_rssi_dbm' => round($predicted, 4),
                'error_db' => round($error, 4),
                'absolute_error_db' => round(abs($error), 4),
            ];
        }

        $mae = array_sum(array_map('abs', $errors)) / count($errors);
        $rmse = sqrt(array_sum(array_map(fn ($error) => $error * $error, $errors)) / count($errors));
        $bias = array_sum($errors) / count($errors);
        $maximum = max(array_map('abs', $errors));
        $holdoutBins = count(array_unique(array_column($holdout, 'bin')));
        $limitations = [
            'MAE is the average size of the RSSI error in dB. Lower is closer to the held-out measurements.',
            'RMSE is like MAE, but a few large mistakes raise it more.',
            'Bias above zero means estimates were stronger than the measured signal. Bias below zero means they were weaker.',
            'Holdout points are spread across the floor plan. A low error in the tested spots does not by itself prove every unmeasured area.',
        ];
        if ($holdoutBins < 2) {
            $limitations[] = 'The held-out points stayed in one area because the survey points are clustered.';
        }
        if (! $scale['calibrated']) {
            $limitations[] = 'The floor plan has no metre scale, so this check used normalized image distances.';
        }

        return ValidationRun::create([
            'survey_id' => $survey->id,
            'executed_by' => $userId,
            'method' => 'idw_spatial_holdout',
            'algorithm_version' => '1.1',
            'status' => 'completed',
            'total_points' => count($points),
            'training_points' => count($training),
            'holdout_points' => count($holdout),
            'holdout_percent' => round((count($holdout) / count($points)) * 100, 3),
            'mean_absolute_error' => round($mae, 4),
            'root_mean_square_error' => round($rmse, 4),
            'mean_bias_error' => round($bias, 4),
            'maximum_absolute_error' => round($maximum, 4),
            'parameters' => [
                'power' => $power,
                'selection' => 'spatial_grid_holdout',
                'distance_unit' => $scale['unit'],
                'holdout_bins' => $holdoutBins,
                'occupied_bins' => count(array_unique(array_column($points, 'bin'))),
            ],
            'predictions' => $predictions,
            'limitations' => $limitations,
            'executed_at' => now(),
        ]);
    }

    private function spatialHoldout(array $points, int $holdoutCount): array
    {
        $bins = [];
        foreach ($points as $index => $point) {
            $bins[$point['bin']][] = $index;
        }
        foreach ($bins as &$members) {
            usort($members, fn ($left, $right) => $points[$left]['observed'] <=> $points[$right]['observed']);
        }
        unset($members);
        ksort($bins);

        $chosen = [];
        $round = 0;
        while (count($chosen) < $holdoutCount) {
            $added = false;
            foreach ($bins as $members) {
                if (count($chosen) >= $holdoutCount || ! isset($members[$round])) {
                    continue;
                }
                $chosen[] = $members[$round];
                $added = true;
            }
            if (! $added) {
                break;
            }
            $round++;
        }

        $holdout = [];
        $training = [];
        foreach ($points as $index => $point) {
            if (in_array($index, $chosen, true)) {
                $holdout[] = $point;
            } else {
                $training[] = $point;
            }
        }

        return [$holdout, $training];
    }

    private function estimate(float $x, float $y, array $training, float $power): float
    {
        $sum = 0;
        $weights = 0;
        foreach ($training as $point) {
            $distance = hypot($x - $point['px'], $y - $point['py']);
            if ($distance < 0.000001) {
                return $point['observed'];
            }
            $weight = 1 / ($distance ** $power);
            $sum += $weight * $point['observed'];
            $weights += $weight;
        }

        return $sum / $weights;
    }
}
