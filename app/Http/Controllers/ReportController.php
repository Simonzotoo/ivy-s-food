<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $service): View
    {
        [$type, $from, $to, $report] = $this->resolvedReport($request, $service);

        return view('reports.index', [
            'reports' => ReportService::REPORTS,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'report' => $report,
        ]);
    }

    public function exportCsv(Request $request, ReportService $service): StreamedResponse
    {
        [$type, $from, $to, $report] = $this->resolvedReport($request, $service);
        $filename = $type.'-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $report['headers']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, ReportService $service): Response
    {
        [$type, $from, $to, $report] = $this->resolvedReport($request, $service);
        $html = view('reports.pdf', [
            'title' => ReportService::REPORTS[$type],
            'from' => $from,
            'to' => $to,
            'report' => $report,
        ])->render();

        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$type.'.pdf"',
        ]);
    }

    private function resolvedReport(Request $request, ReportService $service): array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys(ReportService::REPORTS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $type = $data['type'] ?? 'daily_sales';
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        return [$type, $from, $to, $service->report($type, $from, $to)];
    }
}
