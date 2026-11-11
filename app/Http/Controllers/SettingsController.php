<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        return view('settings.index', [
            'preferences' => $request->user()->notificationPreferences()->get()->keyBy('event_key'),
            'events' => [
                'import.completed' => 'Measurement import completed',
                'survey.validation_required' => 'Survey ready for validation',
                'insight.high' => 'High-severity insight',
                'action.assigned' => 'Action item assigned',
                'report.ready' => 'Report ready',
                'connector.stopped' => 'Connector stopped',
            ],
        ]);
    }
}
