<?php

namespace App\Services;

use App\Models\ComparisonRun;
use App\Models\ProjectProfile;
use App\Models\Survey;
use Carbon\CarbonInterface;

class ReportSnapshotService
{
    public function build(?Survey $survey, ?ComparisonRun $comparison, CarbonInterface $from, CarbonInterface $to): array
    {
        $profile = ProjectProfile::where('is_active', true)->with('institution')->first();
        $snapshot = [
            'project' => [
                'institution' => $profile?->institution?->name,
                'title' => $profile?->project_title,
                'aim' => $profile?->aim,
                'objectives' => $profile?->objectives,
            ],
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'methodology' => [
                'RSSI and performance measurements',
                'IDW heat-map interpolation using calibrated metres when the floor plan has a scale',
                'AP load and channel utilisation',
                'Technical evidence matching, which is not a statistical correlation',
                'Deterministic insight rules',
            ],
            'explanations' => [
                'good_signal_coverage' => 'Good-signal coverage is the share of the usable floor at or above the selected RSSI threshold. Unmeasured area is not counted as good coverage.',
                'estimated_coverage' => 'Estimated coverage is the share of the usable floor that has a signal estimate.',
                'unmeasured_area' => 'Unmeasured area is the share of the usable floor without enough nearby measurements.',
                'rssi_bands' => 'Weak at or below -75 dBm. Fair from -74 to -67. Good from -66 to -60. Strong at or above -59.',
                'health_score' => 'The network performance score weights good-signal coverage 30 percent, throughput 20 percent, latency 15 percent, packet loss 15 percent, stability 10 percent and access-point load 10 percent. A missing part stays missing. The score is not silently reweighted.',
                'sources' => 'Measured values were collected in the app. Imported values came from a CSV file. Estimated values are heat-map predictions. Simulated values are excluded from technical scores.',
                'validation' => 'MAE is the average absolute RSSI error in dB. RMSE rises more when a few predictions are far off. Bias above zero means estimates were stronger than the measured signal. Holdout points are spread across the floor plan.',
            ],
        ];

        if ($survey) {
            $measurements = $survey->points()->with(['measurements' => fn ($query) => $query->where('is_simulated', false)->whereBetween('captured_at', [$from, $to])])->get()->flatMap->measurements;
            $heatmap = $survey->heatmaps()->where('status', 'completed')->whereBetween('generated_at', [$from, $to])->latest('generated_at')->first();
            $validation = $survey->validationRuns()->latest('executed_at')->first();
            $insights = $survey->insights()->whereBetween('generated_at', [$from, $to])->get();
            $parameters = $heatmap?->parameters ?? [];
            $snapshot['survey'] = [
                'id' => $survey->id,
                'name' => $survey->name,
                'location' => $survey->floor->building->name.' · '.$survey->floor->name,
                'floor_plan_version' => $survey->floorPlan?->version,
                'measurement_count' => $measurements->count(),
                'good_signal_coverage_percent' => $heatmap?->coverage_percent,
                'coverage_percent' => $heatmap?->coverage_percent,
                'estimated_area_percent' => $heatmap?->estimated_area_percent,
                'unmeasured_area_percent' => data_get($parameters, 'unmeasured_area_percent'),
                'coverage_definition' => data_get($parameters, 'coverage_definition'),
                'distance_basis' => data_get($parameters, 'distance_unit'),
                'rssi_bands' => data_get($parameters, 'rssi_bands', $snapshot['explanations']['rssi_bands']),
                'source_point_count' => $heatmap?->source_point_count,
                'median_rssi_dbm' => $this->median($measurements->pluck('rssi_dbm')),
                'median_download_mbps' => $this->median($measurements->pluck('download_mbps')),
                'median_latency_ms' => $this->median($measurements->pluck('latency_ms')),
                'median_packet_loss_percent' => $this->median($measurements->pluck('packet_loss_percent')),
                'validation_method' => $validation?->method,
                'validation_mae_db' => $validation?->mean_absolute_error,
                'validation_rmse_db' => $validation?->root_mean_square_error,
                'validation_bias_db' => $validation?->mean_bias_error,
                'provenance' => $measurements->groupBy('source_type')->map->count()->all(),
                'insights' => $insights->map(fn ($insight) => [
                    'title' => $insight->title,
                    'severity' => $insight->severity,
                    'evidence' => $insight->evidence,
                    'recommendation' => $insight->recommendation,
                    'limitations' => $insight->limitations,
                ])->all(),
            ];
        }

        $snapshot['comparison'] = $comparison?->only(['baseline_metrics', 'followup_metrics', 'changes', 'compatibility_checks', 'limitations']);

        return $snapshot;
    }

    private function median($values): ?float
    {
        $values = $values->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value)->sort()->values();
        if ($values->isEmpty()) {
            return null;
        }
        $count = $values->count();

        return round($count % 2 ? $values[intdiv($count, 2)] : ($values[$count / 2 - 1] + $values[$count / 2]) / 2, 3);
    }
}
