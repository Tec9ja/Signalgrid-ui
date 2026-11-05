<?php

namespace App\Services;

use App\Models\SystemCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $path = 'health-checks/'.now()->format('YmdHis').'.txt';
            Storage::disk('local')->put($path, 'SignalGrid health check');
            Storage::disk('local')->delete($path);
            return ['Private storage is writable.', ['disk' => 'local']];
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
