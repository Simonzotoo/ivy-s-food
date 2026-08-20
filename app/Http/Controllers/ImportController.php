<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('imports.index', [
            'types' => ImportService::TYPES,
            'batches' => ImportBatch::query()->with('creator')->latest()->paginate(20),
        ]);
    }

    public function template(string $type, ImportService $service): Response
    {
        abort_unless(array_key_exists($type, ImportService::TYPES), 404);

        return response($service->template($type), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$type.'-template.csv"',
        ]);
    }

    public function store(Request $request, ImportService $service): RedirectResponse
    {
        $data = $request->validate([
            'import_type' => ['required', 'in:'.implode(',', array_keys(ImportService::TYPES))],
            'csv_content' => ['required', 'string'],
            'mode' => ['required', 'in:dry_run,apply'],
        ]);

        $batch = $service->run($data['import_type'], $data['csv_content'], $data['mode'] === 'dry_run', $request->user());

        return redirect()->route('imports.show', $batch)->with('status', 'Import '.$batch->batch_number.' processed.');
    }

    public function show(ImportBatch $batch): View
    {
        return view('imports.show', ['batch' => $batch->load(['errors', 'creator'])]);
    }

    public function rollback(Request $request, ImportBatch $batch, ImportService $service): RedirectResponse
    {
        $service->rollback($batch, $request->user());

        return back()->with('status', 'Rollback request processed.');
    }
}
