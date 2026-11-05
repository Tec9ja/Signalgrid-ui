<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMutatingRequests
{
    public function __construct(private AuditService $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->user() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $this->audit->record(
                'http.'.strtolower($request->method()),
                null,
                [],
                [],
                [
                    'path' => $request->path(),
                    'status_code' => $response->getStatusCode(),
                    'input_keys' => array_values(array_diff(array_keys($request->all()), ['password', 'password_confirmation'])),
                ],
                $request,
            );
        }
        return $response;
    }
}
