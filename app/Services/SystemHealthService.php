<?php

namespace App\Services;

use App\Models\SystemCheck;
use App\Support\PersistentStorage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SystemHealthService
{
    public function run(): array
    {
        $checks = [];
        $checks[] = $this->check('database', function () {
            DB::select('select 1');
            return ['Database connection is operational.', ['connection' => DB::connection()->getName()]];
        });
        $checks[] = $this->check('storage', function () {
            $disk = PersistentStorage::diskName();
            if (app()->environment('production') && $disk !== 's3') {
                throw new RuntimeException('Uploaded floor plans and reports use temporary disk storage. Set AWS_BUCKET and AWS_ACCESS_KEY_ID so files survive a restart. Cloudflare R2 works through AWS_ENDPOINT and AWS_USE_PATH_STYLE_ENDPOINT.');
            }
            $path = 'health-checks/'.now()->format('YmdHis').'.txt';
            $written = PersistentStorage::disk($disk)->put($path, 'SignalGrid health check');
            if ($written === false) {
                throw new RuntimeException('Private storage could not be written.');
            }
            PersistentStorage::disk($disk)->delete($path);

            return ['Private storage is writable.', ['disk' => $disk, 'persistent' => PersistentStorage::persists()]];
        });
        $checks[] = $this->check('queue', function () {
            return ['Queue configuration loaded.', ['connection' => config('queue.default')]];
        });
        $checks[] = $this->check('mail', function () {
            return ['Mail configuration loaded.', ['mailer' => config('mail.default')]];
        });

        return [
            'status' => collect($checks)->every(fn ($check) => $check['status'] === 'ok') ? 'ok' : 'degraded',
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function check(string $key, callable $callback): array
    {
        try {
            [$message, $details] = $callback();
            $status = 'ok';
        } catch (\Throwable $exception) {
            report($exception);
            $status = 'error';
            $message = $exception->getMessage();
            $details = [];
        }

        SystemCheck::updateOrCreate(
            ['check_key' => $key],
            ['status' => $status, 'message' => $message, 'details' => $details, 'checked_at' => now()],
        );

        return compact('key', 'status', 'message', 'details');
    }
}
