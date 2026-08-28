<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Import\ImportTemplates;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ImportController extends Controller
{
    private const COMMIT_PERMISSIONS = [
        'customers' => 'imports.commit_customers',
        'suppliers' => 'imports.commit_suppliers',
        'raw_materials' => 'imports.commit_catalogue',
        'product_variants' => 'imports.commit_catalogue',
        'prices' => 'imports.commit_catalogue',
        'opening_stock' => 'imports.commit_opening_stock',
        'open_credit_balances' => 'imports.commit_opening_stock',
    ];

    public function index(): View
    {
        return view('imports.index', [
            'types' => ImportTemplates::specs(),
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

    public function create(string $type): View
    {
        abort_unless(array_key_exists($type, ImportService::TYPES), 404);

        return view('imports.create', ['type' => $type, 'label' => ImportTemplates::label($type)]);
    }

    public function store(Request $request, ImportService $service): RedirectResponse
    {
        $data = $request->validate([
            'import_type' => ['required', 'in:'.implode(',', array_keys(ImportService::TYPES))],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $batch = $service->upload($data['import_type'], $data['file'], $request->user());

        return redirect()->route('imports.map', $batch)->with('status', 'File uploaded. Review the column mapping below.');
    }

    public function map(ImportBatch $batch, ImportService $service): View
    {
        return view('imports.map', [
            'batch' => $batch,
            'headers' => $service->detectedHeaders($batch),
            'fields' => ImportTemplates::fields($batch->import_type),
            'suggested' => $batch->mapping ?? $service->suggestedMapping($batch),
        ]);
    }

    public function validateMapping(Request $request, ImportBatch $batch, ImportService $service): RedirectResponse
    {
        $fields = array_keys(ImportTemplates::fields($batch->import_type));
        $rules = ['duplicate_strategy' => ['nullable', 'in:skip,update,stop']];
        foreach ($fields as $field) {
            $rules['mapping.'.$field] = ['nullable', 'string'];
        }
        $data = $request->validate($rules);

        $mapping = collect($fields)->mapWithKeys(fn (string $field) => [$field => $data['mapping'][$field] ?? null])->all();

        $service->validate($batch, $mapping, $data['duplicate_strategy'] ?? null, $request->user());

        return redirect()->route('imports.show', $batch)->with('status', 'Preview ready. Review the summary below before importing.');
    }

    public function show(ImportBatch $batch): View
    {
        return view('imports.show', [
            'batch' => $batch->load(['errors', 'creator']),
            'label' => ImportTemplates::label($batch->import_type),
        ]);
    }

    public function errors(ImportBatch $batch): Response
    {
        $fieldKeys = array_keys(ImportTemplates::fields($batch->import_type));
        $lines = [ImportTemplates::csvLine(['row_number', 'message', ...$fieldKeys])];
        foreach ($batch->errors as $error) {
            $rowData = $error->row_data ?? [];
            $lines[] = ImportTemplates::csvLine([
                (string) $error->row_number,
                $error->message,
                ...array_map(fn (string $key): string => (string) ($rowData[$key] ?? ''), $fieldKeys),
            ]);
        }

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$batch->batch_number.'-errors.csv"',
        ]);
    }

    public function commit(Request $request, ImportBatch $batch, ImportService $service): RedirectResponse
    {
        $permission = self::COMMIT_PERMISSIONS[$batch->import_type] ?? null;
        abort_unless($permission && $request->user()->can($permission), 403);

        $request->validate(['confirm' => ['accepted']]);

        $service->commit($batch, $request->user());

        return redirect()->route('imports.show', $batch)->with('status', 'Import '.$batch->batch_number.' committed.');
    }

    public function rollback(Request $request, ImportBatch $batch, ImportService $service): RedirectResponse
    {
        $service->rollback($batch, $request->user());

        return back()->with('status', 'Rollback request processed.');
    }
}
