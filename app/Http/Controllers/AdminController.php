<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, SystemCheck, User};
use App\Services\{AuditService, SystemHealthService};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('users.manage'), 403);
        return view('admin.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(25),
            'checks' => SystemCheck::orderBy('check_key')->get(),
            'unreadNotifications' => $request->user()->unreadNotifications,
        ]);
    }

    public function updateUser(Request $request, User $user, AuditService $audit): JsonResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'status' => ['sometimes', 'in:active,inactive'],
            'role' => ['sometimes', 'exists:roles,name'],
        ]);
        $old = $user->only(['name', 'email', 'status']);
        $role = $data['role'] ?? null;
        unset($data['role']);
        $user->update($data);
        if ($role) $user->syncRoles([$role]);
        $audit->record('user.updated', $user, $old, $user->fresh()->only(['name', 'email', 'status']), ['role' => $role]);
        return response()->json($user->fresh()->load('roles'));
    }

    public function auditLogs(Request $request): View
    {
        abort_unless($request->user()->can('audit.view'), 403);
        $logs = AuditLog::with('user')->latest('occurred_at')->paginate(50);
        return view('admin.audit', compact('logs'));
    }

    public function health(Request $request, SystemHealthService $service): JsonResponse
    {
        abort_unless($request->user()->can('audit.view'), 403);
        return response()->json($service->run());
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json($request->user()->notifications()->latest()->limit(50)->get());
    }

    public function markNotificationRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        return response()->json(['status' => 'read']);
    }

    public function preference(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_key' => ['required', 'in:import.completed,survey.validation_required,insight.high,action.assigned,report.ready,connector.stopped'],
            'database_enabled' => ['required', 'boolean'],
            'mail_enabled' => ['required', 'boolean'],
        ]);
        $preference = $request->user()->notificationPreferences()->updateOrCreate(
            ['event_key' => $data['event_key']],
            $data,
        );
        return response()->json($preference);
    }
}
