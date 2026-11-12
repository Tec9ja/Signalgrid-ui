<?php

namespace App\Http\Controllers;

use App\Models\ComparisonRun;
use App\Models\ProjectProfile;
use App\Models\Report;
use App\Models\Survey;
use App\Services\ReportSnapshotService;
use App\Support\PersistentStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('dashboard.view'), 403);
        $reports = Report::with(['survey.floor.building', 'creator'])->latest('generated_at')->paginate(25);
        $surveys = Survey::with('floor.building')->latest()->get();
        $comparisons = ComparisonRun::with(['baseline', 'followup'])->latest()->get();

        return view('reports.index', compact('reports', 'surveys', 'comparisons'));
    }

    public function store(Request $request, ReportSnapshotService $service)
    {
        abort_unless($request->user()->can('reports.generate'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'report_type' => 'required|in:survey,comparison,stakeholder',
            'survey_id' => 'nullable|required_if:report_type,survey|exists:surveys,id',
            'comparison_run_id' => 'nullable|required_if:report_type,comparison|exists:comparison_runs,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);
        $survey = isset($data['survey_id']) ? Survey::with(['floor.building', 'floorPlan'])->findOrFail($data['survey_id']) : null;
        $comparison = isset($data['comparison_run_id']) ? ComparisonRun::findOrFail($data['comparison_run_id']) : null;
        $from = Carbon::parse($data['period_start']);
        $to = Carbon::parse($data['period_end']);
        $snapshot = $service->build($survey, $comparison, $from, $to);
        $report = Report::create([
            'project_profile_id' => ProjectProfile::where('is_active', true)->value('id'),
            'survey_id' => $survey?->id,
            'comparison_run_id' => $comparison?->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'report_type' => $data['report_type'],
            'status' => 'ready',
            'period_start' => $from,
            'period_end' => $to,
            'filters' => [],
            'data_sources' => ['measured', 'imported', 'estimated', 'simulated', 'controller', 'user_reported'],
            'limitations' => array_values(array_filter([
                'Simulated measurements are excluded from technical calculations.',
                $survey && ! data_get($snapshot, 'survey.coverage_percent') ? 'No completed heat map was available for this period.' : null,
            ])),
            'snapshot' => $snapshot,
            'methodology_version' => '1.1',
            'generated_at' => now(),
        ]);

        return response()->json($report, 201);
    }

    public function preview(Request $request, Report $report): View
    {
        abort_unless($request->user()->can('dashboard.view'), 403);

        return view('reports.preview', compact('report'));
    }

    public function pdf(Request $request, Report $report)
    {
        abort_unless($request->user()->can('reports.generate'), 403);
        $name = 'signalgrid-report-'.$report->id.'.pdf';
        $bytes = Pdf::loadView('reports.pdf', compact('report'))->setPaper('a4', 'portrait')->output();
        $this->storeExport($report, $request->user()->id, 'pdf', $name, $bytes);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    public function csv(Request $request, Report $report)
    {
        abort_unless($request->user()->can('reports.generate'), 403);
        $name = 'signalgrid-report-'.$report->id.'.csv';
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['section', 'metric', 'value']);
        foreach ($this->flatten($report->snapshot ?? []) as [$section, $metric, $value]) {
            fputcsv($handle, [$section, $metric, $value]);
        }
        rewind($handle);
        $bytes = stream_get_contents($handle);
        fclose($handle);
        $this->storeExport($report, $request->user()->id, 'csv', $name, $bytes ?: '');

        return response($bytes ?: '', 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    private function storeExport(Report $report, int $userId, string $format, string $name, string $bytes): void
    {
        $disk = PersistentStorage::diskName();
        $path = "reports/{$report->id}/{$name}";
        PersistentStorage::disk($disk)->put($path, $bytes);
        $report->exports()->create([
            'requested_by' => $userId,
            'format' => $format,
            'status' => 'ready',
            'file_name' => $name,
            'file_path' => $path,
            'file_size' => strlen($bytes),
            'storage_disk' => $disk,
            'completed_at' => now(),
        ]);
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $rows = [];
        foreach ($data as $key => $value) {
            $path = $prefix ? $prefix.'.'.$key : (string) $key;
            if (is_array($value)) {
                $rows = array_merge($rows, $this->flatten($value, $path));
                continue;
            }
            $parts = explode('.', $path);
            $metric = array_pop($parts);
            $rows[] = [implode('.', $parts), $metric, is_bool($value) ? ($value ? 'true' : 'false') : $value];
        }

        return $rows;
    }
}
