<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\SystemThreshold;
use Carbon\CarbonInterface;

class CongestionAnalysisService
{
    public function analyse(AccessPoint $ap, CarbonInterface $from, CarbonInterface $to): array
    {
        $samples = $ap->loadSamples()->whereBetween('sampled_at', [$from, $to])->get();
        if ($samples->isEmpty()) {
            return $this->result('insufficient_data', 'unknown', [], [
                'No AP-load telemetry is available for this period.',
            ]);
        }

        $util = $this->median($samples->pluck('channel_utilisation_percent')->filter(fn ($value) => $value !== null));
        $clients = $this->median($samples->pluck('connected_clients')->filter(fn ($value) => $value !== null));
        $latency = $this->median($samples->pluck('latency_ms')->filter(fn ($value) => $value !== null));
        $loss = $this->median($samples->pluck('packet_loss_percent')->filter(fn ($value) => $value !== null));
        $throughput = $this->median($samples->pluck('throughput_mbps')->filter(fn ($value) => $value !== null));

        $missing = [];
        if ($util === null) {
            $missing[] = 'Channel utilisation is unavailable, so congestion cannot be ruled out.';
        }
        if ($clients === null) {
            $missing[] = 'Connected-client count is unavailable.';
        }
        if ($ap->configured_client_limit === null) {
            $missing[] = 'No client limit is configured for this access point.';
        }
        if ($latency === null && $loss === null && $throughput === null) {
            $missing[] = 'Latency, packet loss and throughput are all unavailable.';
        }

        $evidence = [
            'median_channel_utilisation_percent' => $util,
            'median_connected_clients' => $clients,
            'configured_client_limit' => $ap->configured_client_limit,
            'median_latency_ms' => $latency,
            'median_packet_loss_percent' => $loss,
            'median_throughput_mbps' => $throughput,
            'sample_count' => $samples->count(),
        ];

        if ($missing) {
            return $this->result('insufficient_data', 'unknown', $evidence, $missing);
        }

        $utilHigh = $util > SystemThreshold::number('channel_utilisation_high_percent', 70);
        $clientsHigh = $clients > $ap->configured_client_limit;
        $performanceBad = ($latency !== null && $latency > SystemThreshold::number('latency_poor_ms', 100))
            || ($loss !== null && $loss > SystemThreshold::number('packet_loss_poor_percent', 3))
            || ($throughput !== null && $throughput < SystemThreshold::number('throughput_poor_mbps', 10));

        if ($utilHigh && $clientsHigh && $performanceBad) {
            return $this->result('possible_congestion', 'high', $evidence, [
                'This is possible congestion. It is not proof of the cause.',
            ]);
        }

        return $this->result('no_congestion_detected', 'low', $evidence, [
            'Channel utilisation, client count and at least one performance measure were available. None of them met the congestion rule together.',
        ]);
    }

    private function result(string $classification, string $severity, array $evidence, array $limitations): array
    {
        return [
            'classification' => $classification,
            'severity' => $severity,
            'evidence' => $evidence,
            'rule' => 'LOAD-001',
            'limitations' => $limitations,
        ];
    }

    private function median($values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }
        $sorted = $values->map(fn ($value) => (float) $value)->sort()->values();
        $count = $sorted->count();

        return round($count % 2 ? $sorted[intdiv($count, 2)] : ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2, 3);
    }
}
