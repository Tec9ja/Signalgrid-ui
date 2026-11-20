<?php

namespace App\Services;

use App\Models\MeasurementImport;
use App\Support\PersistentStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CsvMeasurementImportService
{
    public function process(MeasurementImport $import, int $userId): MeasurementImport
    {
        $import->loadMissing('survey');
        $import->update(['status' => 'processing']);

        $disk = $import->storage_disk ?: PersistentStorage::diskName();
        $contents = Storage::disk($disk)->get($import->file_path);
        $temporary = tempnam(sys_get_temp_dir(), 'sgcsv');
        if (! is_string($contents) || $temporary === false || file_put_contents($temporary, $contents) === false) {
            if (is_string($temporary)) {
                @unlink($temporary);
            }
            $import->update([
                'status' => 'failed',
                'error_message' => 'The uploaded CSV file could not be opened.',
            ]);

            return $import->refresh();
        }

        $handle = fopen($temporary, 'r');

        if ($handle === false) {
            @unlink($temporary);
            $import->update([
                'status' => 'failed',
                'error_message' => 'The uploaded CSV file could not be opened.',
            ]);

            return $import->refresh();
        }

        $firstRow = fgetcsv($handle) ?: [];
        $headers = array_map(
            fn ($header) => strtolower(trim((string) $header)),
            $firstRow,
        );

        $hasFloorPlan = (bool) $import->survey->floor_plan_id;
        $required = $hasFloorPlan
            ? ['x_normalized', 'y_normalized', 'rssi_dbm', 'captured_at']
            : ['rssi_dbm', 'captured_at'];
        $missing = array_diff($required, $headers);

        if ($missing !== []) {
            fclose($handle);
            @unlink($temporary);

            $import->update([
                'status' => 'failed',
                'error_message' => 'Missing columns: '.implode(', ', $missing),
            ]);

            return $import->refresh();
        }

        $rowNumber = 1;
        $accepted = 0;
        $rejected = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($values === [null] || $values === []) {
                continue;
            }

            $values = array_pad($values, count($headers), null);
            $values = array_slice($values, 0, count($headers));
            $data = array_combine($headers, $values);

            if ($data === false) {
                $import->errors()->create([
                    'row_number' => $rowNumber,
                    'row_data' => $values,
                    'errors' => ['The row does not match the CSV header structure.'],
                ]);
                $rejected++;
                continue;
            }

            $data = array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                $data,
            );

            $errors = $this->validateRow($data, $hasFloorPlan);

            if ($errors !== []) {
                $import->errors()->create([
                    'row_number' => $rowNumber,
                    'row_data' => $data,
                    'errors' => $errors,
                ]);
                $rejected++;
                continue;
            }

            try {
                DB::transaction(function () use ($import, $userId, $data, $hasFloorPlan): void {
                    $point = $import->survey->points()->create([
                        'floor_plan_id' => $import->survey->floor_plan_id,
                        'survey_area_id' => $import->survey->survey_area_id,
                        'captured_by' => $userId,
                        'x_normalized' => $hasFloorPlan ? (float) $data['x_normalized'] : null,
                        'y_normalized' => $hasFloorPlan ? (float) $data['y_normalized'] : null,
                        'source_type' => 'imported',
                        'collection_method' => 'csv',
                        'validation_status' => 'valid',
                        'captured_at' => $data['captured_at'],
                    ]);

                    $point->measurements()->create([
                        'measurement_import_id' => $import->id,
                        'ssid' => $this->nullableString($data, 'ssid'),
                        'bssid_hash' => $this->bssidHash($data),
                        'rssi_dbm' => (int) $data['rssi_dbm'],
                        'download_mbps' => $this->nullableNumber($data, 'download_mbps'),
                        'upload_mbps' => $this->nullableNumber($data, 'upload_mbps'),
                        'latency_ms' => $this->nullableNumber($data, 'latency_ms'),
                        'jitter_ms' => $this->nullableNumber($data, 'jitter_ms'),
                        'packet_loss_percent' => $this->nullableNumber($data, 'packet_loss_percent'),
                        'channel' => $this->nullableInteger($data, 'channel'),
                        'frequency_band' => $this->nullableString($data, 'frequency_band'),
                        'noise_floor_dbm' => $this->nullableInteger($data, 'noise_floor_dbm'),
                        'source_type' => 'imported',
                        'collection_method' => 'csv',
                        'is_simulated' => false,
                        'quality_flags' => [],
                        'captured_at' => $data['captured_at'],
                        'raw_payload' => $data,
                    ]);
                });

                $accepted++;
            } catch (Throwable $exception) {
                report($exception);

                $import->errors()->create([
                    'row_number' => $rowNumber,
                    'row_data' => $data,
                    'errors' => ['The row could not be saved: '.$exception->getMessage()],
                ]);
                $rejected++;
            }
        }

        fclose($handle);
        @unlink($temporary);

        $import->update([
            'status' => 'completed',
            'total_rows' => $accepted + $rejected,
            'accepted_rows' => $accepted,
            'rejected_rows' => $rejected,
            'processed_at' => now(),
        ]);

        return $import->refresh()->load('survey');
    }

    private function validateRow(array $data, bool $hasFloorPlan): array
    {
        $errors = [];
        $x = $data['x_normalized'] ?? null;
        $y = $data['y_normalized'] ?? null;
        $xBlank = $x === null || $x === '';
        $yBlank = $y === null || $y === '';

        if ($hasFloorPlan) {
            foreach (['x_normalized', 'y_normalized'] as $key) {
                if (! is_numeric($data[$key] ?? null)
                    || (float) $data[$key] < 0
                    || (float) $data[$key] > 1) {
                    $errors[] = "$key must be between 0 and 1";
                }
            }
        } elseif (! $xBlank || ! $yBlank) {
            $errors[] = 'Leave x_normalized and y_normalized blank. This survey has no floor plan.';
        }

        if (! is_numeric($data['rssi_dbm'] ?? null)
            || (int) $data['rssi_dbm'] < -120
            || (int) $data['rssi_dbm'] > 0) {
            $errors[] = 'rssi_dbm must be between -120 and 0';
        }

        if (strtotime((string) ($data['captured_at'] ?? '')) === false) {
            $errors[] = 'captured_at is invalid';
        }

        foreach ([
            'download_mbps',
            'upload_mbps',
            'latency_ms',
            'jitter_ms',
        ] as $key) {
            $value = $data[$key] ?? null;
            if ($value !== null && $value !== '' && (! is_numeric($value) || (float) $value < 0)) {
                $errors[] = "$key must be zero or greater";
            }
        }

        $packetLoss = $data['packet_loss_percent'] ?? null;
        if ($packetLoss !== null && $packetLoss !== ''
            && (! is_numeric($packetLoss)
                || (float) $packetLoss < 0
                || (float) $packetLoss > 100)) {
            $errors[] = 'packet_loss_percent must be between 0 and 100';
        }

        return $errors;
    }

    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function nullableNumber(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function nullableInteger(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function bssidHash(array $data): ?string
    {
        $bssid = $this->nullableString($data, 'bssid');
        return $bssid === null ? null : hash('sha256', strtolower($bssid));
    }
}
