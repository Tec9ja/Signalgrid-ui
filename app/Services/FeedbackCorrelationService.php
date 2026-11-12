<?php

namespace App\Services;

use App\Models\ApLoadSample;
use App\Models\FeedbackResponse;
use App\Models\Measurement;

class FeedbackCorrelationService
{
    public function correlate(FeedbackResponse $feedback, int $windowMinutes = 30): array
    {
        $from = $feedback->experienced_at->copy()->subMinutes($windowMinutes);
        $to = $feedback->experienced_at->copy()->addMinutes($windowMinutes);
        $measurements = Measurement::query()
            ->where('is_simulated', false)
            ->whereBetween('captured_at', [$from, $to])
            ->whereHas('point.survey', function ($query) use ($feedback) {
                $query->where('floor_id', $feedback->floor_id);
                if ($feedback->survey_area_id) {
                    $query->where('survey_area_id', $feedback->survey_area_id);
                }
            })
            ->get();
        $apSamples = ApLoadSample::query()
            ->whereBetween('sampled_at', [$from, $to])
            ->whereHas('accessPoint', function ($query) use ($feedback) {
                $query->where('floor_id', $feedback->floor_id);
                if ($feedback->survey_area_id) {
                    $query->where('survey_area_id', $feedback->survey_area_id);
                }
            })
            ->get();

        $rows = [];
        foreach ($measurements as $measurement) {
            $minutes = abs($measurement->captured_at->diffInMinutes($feedback->experienced_at, false));
            $rows[] = $this->row($feedback, $minutes, $measurement->id, null, [
                'rssi_dbm' => $measurement->rssi_dbm,
                'download_mbps' => $measurement->download_mbps,
                'latency_ms' => $measurement->latency_ms,
                'packet_loss_percent' => $measurement->packet_loss_percent,
                'problem_type' => $feedback->problem_type,
            ]);
        }
        foreach ($apSamples as $sample) {
            $minutes = abs($sample->sampled_at->diffInMinutes($feedback->experienced_at, false));
            $rows[] = $this->row($feedback, $minutes, null, $sample->id, [
                'connected_clients' => $sample->connected_clients,
                'channel_utilisation_percent' => $sample->channel_utilisation_percent,
                'latency_ms' => $sample->latency_ms,
                'packet_loss_percent' => $sample->packet_loss_percent,
                'problem_type' => $feedback->problem_type,
            ]);
        }

        $interpretation = $this->interpret($feedback, $rows, $measurements, $apSamples, $windowMinutes);
        $created = [];
        foreach ($rows as $row) {
            $row['evidence']['interpretation'] = $interpretation['code'];
            $row['evidence']['interpretation_label'] = $interpretation['label'];
            $row['evidence']['statistical_correlation'] = false;
            $created[] = $feedback->correlations()->updateOrCreate(
                ['measurement_id' => $row['measurement_id'], 'ap_load_sample_id' => $row['ap_load_sample_id']],
                [
                    'strength' => $row['strength'],
                    'time_difference_minutes' => $row['time_difference_minutes'],
                    'location_match' => $row['location_match'],
                    'evidence' => $row['evidence'],
                    'calculation_version' => '1.1',
                ],
            );
        }

        return [
            'measurement_matches' => $measurements->count(),
            'ap_load_matches' => $apSamples->count(),
            'total_correlations' => count($created),
            'overall_strength' => $interpretation['strength'],
            'interpretation' => $interpretation['code'],
            'interpretation_label' => $interpretation['label'],
            'statistical_correlation' => false,
            'limitations' => $interpretation['limitations'],
        ];
    }

    private function row(FeedbackResponse $feedback, float $minutes, ?int $measurementId, ?int $sampleId, array $evidence): array
    {
        return [
            'measurement_id' => $measurementId,
            'ap_load_sample_id' => $sampleId,
            'strength' => $this->strength($minutes, $feedback->survey_area_id !== null),
            'time_difference_minutes' => $minutes,
            'location_match' => $feedback->survey_area_id ? 'survey_area' : 'floor',
            'evidence' => $evidence,
        ];
    }

    private function interpret(FeedbackResponse $feedback, array $rows, $measurements, $apSamples, int $windowMinutes): array
    {
        if ($rows === []) {
            return [
                'code' => 'insufficient_supporting_measurements',
                'label' => 'Insufficient supporting measurements',
                'strength' => 'insufficient_data',
                'limitations' => ["No technical measurements were found in the selected location and ±{$windowMinutes} minute window."],
            ];
        }

        $strengths = collect($rows)->pluck('strength');
        $proximity = $strengths->contains('strong') ? 'strong' : ($strengths->contains('moderate') ? 'moderate' : 'weak');
        if (count($rows) < 5 || $proximity === 'weak') {
            return [
                'code' => 'matched_insufficient',
                'label' => 'Matched, but supporting measurements are insufficient',
                'strength' => $proximity,
                'limitations' => ['Fewer than five matches, or only a weak location and time match, is not enough to treat this as supporting evidence. This is not a statistical correlation.'],
            ];
        }

        $agreement = $this->agreement($feedback, $measurements, $apSamples);
        if ($agreement === true) {
            return [
                'code' => 'evidence_supports_report',
                'label' => 'Evidence supports the report. This is not a statistical correlation.',
                'strength' => $proximity,
                'limitations' => ['The matched measurements point the same way as the report. A location and time match is not a statistical correlation.'],
            ];
        }
        if ($agreement === false) {
            return [
                'code' => 'matched_not_supported',
                'label' => 'Matched, but the measurements do not support the report',
                'strength' => $proximity,
                'limitations' => ['Measurements were found nearby, but they do not support this problem type. This is not a statistical correlation.'],
            ];
        }

        return [
            'code' => 'matched_unverified',
            'label' => 'Matched. This problem type has no verified technical check.',
            'strength' => $proximity,
            'limitations' => ['A nearby measurement was found, but this problem type has no technical check that can confirm it.'],
        ];
    }

    private function agreement(FeedbackResponse $feedback, $measurements, $apSamples): ?bool
    {
        $rssi = $measurements->pluck('rssi_dbm')->filter(fn ($value) => $value !== null);
        $download = $measurements->pluck('download_mbps')->filter(fn ($value) => $value !== null);
        $latency = $measurements->pluck('latency_ms')->merge($apSamples->pluck('latency_ms'))->filter(fn ($value) => $value !== null);
        $loss = $measurements->pluck('packet_loss_percent')->merge($apSamples->pluck('packet_loss_percent'))->filter(fn ($value) => $value !== null);

        return match ($feedback->problem_type) {
            'weak_signal', 'cannot_connect' => $rssi->isEmpty() ? null : $this->median($rssi) <= -75,
            'slow_connection' => ($download->isEmpty() && $latency->isEmpty()) ? null : (($download->isNotEmpty() && $this->median($download) < 10) || ($latency->isNotEmpty() && $this->median($latency) > 100)),
            'high_latency' => $latency->isEmpty() ? null : $this->median($latency) > 100,
            'frequent_disconnection', 'buffering' => ($loss->isEmpty() && $download->isEmpty()) ? null : (($loss->isNotEmpty() && $this->median($loss) > 3) || ($download->isNotEmpty() && $this->median($download) < 10)),
            default => null,
        };
    }

    private function strength(float $minutes, bool $exactArea): string
    {
        if ($exactArea && $minutes <= 15) {
            return 'strong';
        }
        if ($minutes <= 30) {
            return 'moderate';
        }

        return 'weak';
    }

    private function median($values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }
        $sorted = $values->map(fn ($value) => (float) $value)->sort()->values();
        $count = $sorted->count();

        return $count % 2 ? $sorted[intdiv($count, 2)] : ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
    }
}
