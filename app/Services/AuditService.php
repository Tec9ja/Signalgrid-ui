<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    public function record(
        string $event,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'route_name' => $request->route()?->getName(),
            'request_id' => $request->headers->get('X-Request-ID', (string) Str::uuid()),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip().config('app.key')) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'metadata' => $this->redact($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function redact(array $data): array
    {
        $blocked = ['password', 'password_confirmation', 'token', 'api_token', 'secret'];
        foreach ($blocked as $key) {
            if (array_key_exists($key, $data)) $data[$key] = '[REDACTED]';
        }
        return $data;
    }
}
