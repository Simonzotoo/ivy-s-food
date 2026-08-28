<?php

namespace App\Services;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialCategory;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Import\ImportTemplates;
use App\Services\Import\SpreadsheetReaderFactory;
use App\Support\PhoneNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImportService
{
    public const TYPES = [
        'customers' => 'Customers',
        'suppliers' => 'Suppliers',
        'raw_materials' => 'Raw materials',
        'product_variants' => 'Product update (status)',
        'prices' => 'Prices',
        'opening_stock' => 'Opening stock',
        'open_credit_balances' => 'Open credit balances',
    ];

    public const DISK = 'local';

    public const MAX_FILE_BYTES = 5 * 1024 * 1024;

    public const MAX_ROWS = 20000;

    public const MAX_STORED_ERRORS = 500;

    public function __construct(private readonly AuditService $audit) {}

    public function template(string $type): string
    {
        return ImportTemplates::csvTemplate($type);
    }

    public function upload(string $type, UploadedFile $file, User $actor): ImportBatch
    {
        if (! array_key_exists($type, self::TYPES)) {
            throw ValidationException::withMessages(['import_type' => 'Unknown import type.']);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            throw ValidationException::withMessages(['file' => 'Only .csv or .xlsx files are accepted.']);
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_FILE_BYTES) {
            throw ValidationException::withMessages(['file' => 'The file is too large. The maximum size is 5 MB.']);
        }

        $this->assertGenuineFile($file, $extension);

        $storedPath = $file->storeAs('imports/'.now()->format('Y/m'), Str::uuid()->toString().'.'.$extension, self::DISK);
        if ($storedPath === false) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        $absolutePath = Storage::disk(self::DISK)->path($storedPath);
        $checksum = hash_file('sha256', $absolutePath);

        try {
            $headers = SpreadsheetReaderFactory::make($absolutePath, $extension)->headers();
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($storedPath);

            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        if ($headers === [] || $headers === ['']) {
            Storage::disk(self::DISK)->delete($storedPath);

            throw ValidationException::withMessages(['file' => 'No column headings were found in the first row of this file.']);
        }

        $batch = ImportBatch::query()->create([
            'batch_number' => $this->nextBatchNumber(),
            'import_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'file_disk' => self::DISK,
            'file_path' => $storedPath,
            'checksum' => $checksum,
            'status' => 'uploaded',
            'dry_run' => true,
            'created_by' => $actor->id,
        ]);

        $this->audit->record('import.uploaded', $batch, [], [
            'import_type' => $type,
            'original_filename' => $batch->original_filename,
            'checksum' => $checksum,
        ], actor: $actor);

        return $batch;
    }

    /**
     * @return array<string, string|null>
     */
    public function suggestedMapping(ImportBatch $batch): array
    {
        return ImportTemplates::suggestMapping($batch->import_type, $this->reader($batch)->headers());
    }

    /**
     * @return list<string>
     */
    public function detectedHeaders(ImportBatch $batch): array
    {
        return $this->reader($batch)->headers();
    }

    /**
     * @param  array<string, string|null>  $mapping  target field => source column header
     */
    public function validate(ImportBatch $batch, array $mapping, ?string $duplicateStrategy, User $actor): ImportBatch
    {
        $duplicateStrategy = in_array($duplicateStrategy, ['skip', 'update', 'stop'], true) ? $duplicateStrategy : 'stop';

        return DB::transaction(function () use ($batch, $mapping, $duplicateStrategy, $actor): ImportBatch {
            $batch->errors()->delete();

            $counts = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicate' => 0, 'new' => 0, 'update' => 0, 'skip' => 0];
            $seenKeys = [];
            $storedErrors = 0;

            foreach ($this->reader($batch)->rows() as $rawRow) {
                $counts['total']++;
                if ($counts['total'] > self::MAX_ROWS) {
                    $batch->update(['status' => 'error', 'summary' => ['error' => 'This file has more than '.self::MAX_ROWS.' rows. Please split it into smaller files.']]);

                    return $batch->fresh();
                }

                $row = $this->applyMapping($mapping, $rawRow);
                $result = $this->classifyRow($batch->import_type, $row, $duplicateStrategy, $seenKeys, $actor);

                match ($result['classification']) {
                    'invalid' => $counts['invalid']++,
                    'duplicate_skip', 'duplicate_stop' => $counts['duplicate']++,
                    'update' => $counts['duplicate']++,
                    'new' => $counts['new']++,
                    default => null,
                };

                if ($result['classification'] === 'update') {
                    $counts['update']++;
                } elseif (in_array($result['classification'], ['duplicate_skip', 'duplicate_stop'], true)) {
                    $counts['skip']++;
                }

                if ($result['classification'] === 'invalid') {
                    if ($storedErrors < self::MAX_STORED_ERRORS) {
                        $batch->errors()->create(['row_number' => $counts['total'] + 1, 'message' => implode(' ', $result['errors']), 'row_data' => $row]);
                        $storedErrors++;
                    }
                } else {
                    $counts['valid']++;
                }
            }

            $batch->update([
                'mapping' => $mapping,
                'duplicate_strategy' => $duplicateStrategy,
                'total_rows' => $counts['total'],
                'valid_rows' => $counts['valid'],
                'error_rows' => $counts['invalid'],
                'duplicate_rows' => $counts['duplicate'],
                'new_rows' => $counts['new'],
                'update_rows' => $counts['update'],
                'skipped_rows' => $counts['skip'],
                'status' => $counts['invalid'] > 0 ? 'previewed_with_errors' : 'previewed',
                'dry_run' => true,
            ]);

            $this->audit->record('import.validated', $batch, [], $batch->fresh()->only([
                'total_rows', 'valid_rows', 'error_rows', 'duplicate_rows', 'new_rows', 'update_rows', 'skipped_rows',
            ]), actor: $actor);

            return $batch->fresh(['errors']);
        });
    }

    public function commit(ImportBatch $batch, User $actor): ImportBatch
    {
        if (! in_array($batch->status, ['previewed', 'previewed_with_errors'], true)) {
            throw ValidationException::withMessages(['batch' => 'This import must be validated before it can be committed.']);
        }

        if ($batch->duplicate_strategy === 'stop' && $batch->duplicate_rows > 0) {
            $batch->update(['status' => 'commit_blocked']);

            throw ValidationException::withMessages(['batch' => 'This import contains duplicate rows and the "Stop and review" option is selected. Resolve the duplicates or choose a different duplicate option, then validate again.']);
        }

        return DB::transaction(function () use ($batch, $actor): ImportBatch {
            $mapping = $batch->mapping ?? [];
            $seenKeys = [];
            $created = [];
            $updated = [];
            $createdCustomerIds = [];
            $rowNumber = 1;

            foreach ($this->reader($batch)->rows() as $rawRow) {
                $rowNumber++;
                $row = $this->applyMapping($mapping, $rawRow);
                $result = $this->classifyRow($batch->import_type, $row, $batch->duplicate_strategy, $seenKeys, $actor);

                if (in_array($result['classification'], ['invalid', 'duplicate_skip', 'duplicate_stop'], true)) {
                    continue;
                }

                $applyResult = $this->applyRow($batch->import_type, $row, $result, $actor);
                foreach ($applyResult as $entry) {
                    if ($entry === null) {
                        continue;
                    }
                    if (($entry['action'] ?? null) === 'created') {
                        $created[] = $entry;
                        if ($entry['model'] === Customer::class) {
                            $createdCustomerIds[] = $entry['id'];
                        }
                    } else {
                        $updated[] = $entry;
                    }
                }
            }

            $batch->update([
                'status' => $batch->error_rows > 0 ? 'applied_with_errors' : 'applied',
                'dry_run' => false,
                'applied_at' => now(),
                'confirmed_at' => now(),
                'summary' => ['created' => count($created), 'updated' => count($updated)],
                'rollback_metadata' => [
                    'created' => $created,
                    'updated' => $updated,
                    'created_customer_ids' => $createdCustomerIds,
                ],
            ]);

            $this->audit->record('import.committed', $batch, [], $batch->fresh()->only([
                'status', 'valid_rows', 'error_rows', 'new_rows', 'update_rows', 'skipped_rows', 'summary',
            ]) + ['created_record_ids' => array_column($created, 'id'), 'updated_record_ids' => array_column($updated, 'id')], actor: $actor);

            return $batch->fresh(['errors']);
        });
    }

    public function rollback(ImportBatch $batch, User $actor): ImportBatch
    {
        return DB::transaction(function () use ($batch, $actor): ImportBatch {
            if (! in_array($batch->status, ['applied', 'applied_with_errors'], true)) {
                $batch->update(['status' => 'rollback_not_supported']);

                return $batch->fresh();
            }

            $meta = $batch->rollback_metadata ?? [];
            $retained = [];
            $removed = [];

            foreach (($meta['created'] ?? []) as $item) {
                $model = $item['model'] ?? null;
                $id = $item['id'] ?? null;
                if (! $model || ! $id || ! class_exists($model)) {
                    continue;
                }

                if ($model === Customer::class) {
                    $customer = Customer::query()->find($id);
                    if (! $customer) {
                        continue;
                    }
                    if (Sale::query()->where('customer_id', $id)->exists() || CreditInvoice::query()->where('customer_id', $id)->exists()) {
                        $retained[] = ['model' => $model, 'id' => $id, 'reason' => 'Has sales or credit records since import.'];

                        continue;
                    }
                    $customer->delete();
                    $removed[] = ['model' => $model, 'id' => $id];

                    continue;
                }

                $model::query()->whereKey($id)->delete();
                $removed[] = ['model' => $model, 'id' => $id];
            }

            $old = $batch->only(['status', 'rollback_metadata']);
            $batch->update([
                'status' => $retained === [] ? 'rolled_back' : 'rolled_back_partial',
                'rollback_metadata' => $meta + ['removed' => $removed, 'retained' => $retained],
            ]);

            $this->audit->record('import.rolled_back', $batch, $old, $batch->fresh()->only(['status', 'rollback_metadata']), actor: $actor);

            return $batch->fresh();
        });
    }

    private function reader(ImportBatch $batch): Import\SpreadsheetReader
    {
        if (! $batch->file_path || ! Storage::disk($batch->file_disk ?? self::DISK)->exists($batch->file_path)) {
            throw new RuntimeException('The uploaded file for this import is no longer available.');
        }

        $absolutePath = Storage::disk($batch->file_disk ?? self::DISK)->path($batch->file_path);
        $extension = strtolower(pathinfo($batch->file_path, PATHINFO_EXTENSION));

        return SpreadsheetReaderFactory::make($absolutePath, $extension);
    }

    /**
     * @param  array<string, string|null>  $mapping
     * @param  array<string, string>  $rawRow
     * @return array<string, string>
     */
    private function applyMapping(array $mapping, array $rawRow): array
    {
        $row = [];
        foreach ($mapping as $field => $sourceHeader) {
            $row[$field] = $sourceHeader !== null ? trim((string) ($rawRow[$sourceHeader] ?? '')) : '';
        }

        return $row;
    }

    private function nextBatchNumber(): string
    {
        return 'IMP-'.now()->format('Ymd').'-'.str_pad((string) (ImportBatch::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function assertGenuineFile(UploadedFile $file, string $extension): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Unable to read the uploaded file.']);
        }
        $head = fread($handle, 8) ?: '';
        fclose($handle);

        $isZipSignature = str_starts_with($head, "PK\x03\x04");

        if ($extension === 'xlsx' && ! $isZipSignature) {
            throw ValidationException::withMessages(['file' => 'This does not look like a genuine Excel (.xlsx) file.']);
        }

        if ($extension === 'csv' && ($isZipSignature || str_contains($head, "\0"))) {
            throw ValidationException::withMessages(['file' => 'This does not look like a genuine CSV text file.']);
        }
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, array{normalized_phone: string|null, email: string|null}>  $seenKeys
     * @return array{classification: string, errors: list<string>, matched_id?: int}
     */
    private function classifyRow(string $type, array $row, string $duplicateStrategy, array &$seenKeys, User $actor): array
    {
        return match ($type) {
            'customers' => $this->classifyCustomerRow($row, $duplicateStrategy, $seenKeys, $actor),
            default => $this->classifyLegacyRow($type, $row),
        };
    }

    /**
     * @param  array<string, string>  $row
     */
    private function classifyCustomerRow(array $row, string $duplicateStrategy, array &$seenKeys, User $actor): array
    {
        $errors = [];

        if (blank($row['name'] ?? null)) {
            $errors[] = 'Customer name is required.';
        }

        $type = filled($row['customer_type'] ?? null)
            ? CustomerType::query()->whereRaw('LOWER(name) = ?', [strtolower($row['customer_type'])])->first()
            : null;
        if (blank($row['customer_type'] ?? null)) {
            $errors[] = 'Customer type is required.';
        } elseif (! $type) {
            $errors[] = 'Unknown customer type "'.$row['customer_type'].'".';
        }

        if (filled($row['sale_type'] ?? null) && ! SaleType::query()->whereRaw('LOWER(name) = ?', [strtolower($row['sale_type'])])->exists()) {
            $errors[] = 'Unknown default sales type "'.$row['sale_type'].'".';
        }

        if (filled($row['email'] ?? null) && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        }

        $status = strtolower((string) ($row['status'] ?? '')) ?: 'active';
        if (! in_array($status, ['active', 'inactive', 'suspended'], true)) {
            $errors[] = 'Account status must be active, inactive or suspended.';
        }

        $creditAllowed = $this->parseBoolean($row['credit_allowed'] ?? null);
        if ($creditAllowed && blank($row['credit_limit'] ?? null)) {
            $errors[] = 'Credit limit is required when credit allowed is Yes.';
        }
        if (filled($row['credit_limit'] ?? null) && ! is_numeric($row['credit_limit'])) {
            $errors[] = 'Credit limit must be a number.';
        }

        $normalizedPhone = PhoneNormalizer::ghana($row['phone'] ?? null);
        $email = filled($row['email'] ?? null) ? strtolower(trim($row['email'])) : null;

        if ($errors !== []) {
            return ['classification' => 'invalid', 'errors' => $errors];
        }

        $phoneKey = $normalizedPhone ? 'phone:'.$normalizedPhone : null;
        $emailKey = $email ? 'email:'.$email : null;

        if ($phoneKey !== null || $emailKey !== null) {
            if (($phoneKey !== null && isset($seenKeys[$phoneKey])) || ($emailKey !== null && isset($seenKeys[$emailKey]))) {
                return ['classification' => 'duplicate_skip', 'errors' => ['Duplicate of an earlier row in this same file.']];
            }
            if ($phoneKey !== null) {
                $seenKeys[$phoneKey] = true;
            }
            if ($emailKey !== null) {
                $seenKeys[$emailKey] = true;
            }

            $existing = Customer::query()
                ->when($normalizedPhone, fn ($q) => $q->orWhere('normalized_phone', $normalizedPhone))
                ->when($email, fn ($q) => $q->orWhere('email', $email))
                ->first();

            if ($existing) {
                return match ($duplicateStrategy) {
                    'update' => ['classification' => 'update', 'errors' => [], 'matched_id' => $existing->id],
                    'stop' => ['classification' => 'duplicate_stop', 'errors' => ['Matches existing customer "'.$existing->name.'".']],
                    default => ['classification' => 'duplicate_skip', 'errors' => ['Matches existing customer "'.$existing->name.'".']],
                };
            }
        }

        return ['classification' => 'new', 'errors' => []];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function classifyLegacyRow(string $type, array $row): array
    {
        $errors = [];
        $required = match ($type) {
            'product_variants', 'prices' => ['sku'],
            'raw_materials' => ['name', 'category', 'base_unit_symbol'],
            'suppliers' => ['name'],
            'opening_stock' => ['stock_type', 'sku_or_name', 'batch_number', 'quantity'],
            'open_credit_balances' => ['customer_name', 'amount', 'due_date'],
            default => [],
        };

        foreach ($required as $field) {
            if (blank($row[$field] ?? null)) {
                $errors[] = $field.' is required.';
            }
        }

        if ($errors !== []) {
            return ['classification' => 'invalid', 'errors' => $errors];
        }

        if (in_array($type, ['product_variants', 'prices'], true) && ! ProductVariant::query()->where('sku', $row['sku'])->exists()) {
            return ['classification' => 'invalid', 'errors' => ['SKU does not exist.']];
        }

        if ($type === 'opening_stock' && ! in_array($row['stock_type'], ['raw', 'finished'], true)) {
            return ['classification' => 'invalid', 'errors' => ['Stock type must be raw or finished.']];
        }

        if ($type === 'open_credit_balances' && ! Customer::query()->whereRaw('LOWER(name) = ?', [strtolower($row['customer_name'])])->exists()) {
            return ['classification' => 'invalid', 'errors' => ['Customer name does not match an existing customer.']];
        }

        return ['classification' => 'new', 'errors' => []];
    }

    /**
     * @param  array<string, string>  $row
     * @param  array{classification: string, matched_id?: int}  $classification
     * @return list<array{model: class-string, id: int, action: string}|null>
     */
    private function applyRow(string $type, array $row, array $classification, User $actor): array
    {
        return match ($type) {
            'customers' => [$this->applyCustomer($row, $classification, $actor)],
            'suppliers' => [$this->applySupplier($row)],
            'raw_materials' => [$this->applyMaterial($row)],
            'product_variants' => [$this->applyVariant($row)],
            'prices' => [$this->applyVariant($row)],
            'opening_stock' => $this->applyOpeningStock($row, $actor),
            'open_credit_balances' => [$this->applyOpeningCredit($row, $actor)],
            default => [],
        };
    }

    /**
     * @param  array<string, string>  $row
     */
    private function applyCustomer(array $row, array $classification, User $actor): array
    {
        $type = CustomerType::query()->whereRaw('LOWER(name) = ?', [strtolower($row['customer_type'])])->first();
        $saleType = filled($row['sale_type'] ?? null)
            ? SaleType::query()->whereRaw('LOWER(name) = ?', [strtolower($row['sale_type'])])->first()
            : null;

        $creditAllowed = $this->parseBoolean($row['credit_allowed'] ?? null) && $actor->can('customers.manage_credit');

        $data = array_filter([
            'customer_type_id' => $type?->id,
            'default_sale_type_id' => $saleType?->id,
            'name' => $row['name'] ?: null,
            'phone' => $row['phone'] ?: null,
            'normalized_phone' => PhoneNormalizer::ghana($row['phone'] ?? null),
            'email' => filled($row['email'] ?? null) ? strtolower(trim($row['email'])) : null,
            'location' => $row['location'] ?: null,
            'address' => $row['address'] ?: null,
            'status' => strtolower((string) ($row['status'] ?? '')) ?: 'active',
            'credit_limit' => $creditAllowed ? $row['credit_limit'] : null,
            'notes' => $row['notes'] ?: null,
            'communication_consent' => $this->parseBoolean($row['communication_consent'] ?? null),
        ], fn ($value) => $value !== null);

        if ($classification['classification'] === 'update' && isset($classification['matched_id'])) {
            $customer = Customer::query()->findOrFail($classification['matched_id']);
            $old = $customer->only(array_keys($data));
            $customer->update($data);
            $this->audit->record('customer.updated', $customer, $old, $customer->fresh()->only(array_keys($data)), actor: $actor);

            return ['model' => Customer::class, 'id' => $customer->id, 'action' => 'updated'];
        }

        $customer = Customer::query()->create($data + ['communication_consent' => $data['communication_consent'] ?? false]);
        $this->audit->record('customer.created', $customer, [], $customer->toArray(), actor: $actor);

        return ['model' => Customer::class, 'id' => $customer->id, 'action' => 'created'];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function applyVariant(array $row): ?array
    {
        $variant = ProductVariant::query()->where('sku', $row['sku'])->first();
        $variant?->update(collect($row)->except(['sku'])->filter(fn ($value) => $value !== '')->all());

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function applyMaterial(array $row): array
    {
        $unit = MeasurementUnit::query()->firstOrCreate(['symbol' => $row['base_unit_symbol']], ['name' => $row['base_unit_symbol'], 'type' => 'unit']);
        $category = MaterialCategory::query()->firstOrCreate(['slug' => Str::slug($row['category'])], ['name' => $row['category'], 'type' => 'raw_material']);
        $existing = Material::query()->where('name', $row['name'])->first();
        $material = Material::query()->updateOrCreate(['name' => $row['name']], ['material_category_id' => $category->id, 'base_unit_id' => $unit->id, 'sku' => $row['sku'] ?: null, 'reorder_level' => $row['reorder_level'] ?: null, 'status' => 'active']);

        return ['model' => Material::class, 'id' => $material->id, 'action' => $existing ? 'updated' : 'created'];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function applySupplier(array $row): array
    {
        $existing = Supplier::query()->where('name', $row['name'])->first();
        $supplier = Supplier::query()->updateOrCreate(['name' => $row['name']], ['phone' => $row['phone'] ?: null, 'location' => $row['location'] ?: null, 'email' => $row['email'] ?: null, 'address' => $row['address'] ?: null, 'status' => $row['status'] ?: 'active']);

        return ['model' => Supplier::class, 'id' => $supplier->id, 'action' => $existing ? 'updated' : 'created'];
    }

    /**
     * @param  array<string, string>  $row
     * @return list<array{model: class-string, id: int, action: string}>
     */
    private function applyOpeningStock(array $row, User $actor): array
    {
        if ($row['stock_type'] === 'raw') {
            $material = Material::query()->where('sku', $row['sku_or_name'])->orWhere('name', $row['sku_or_name'])->firstOrFail();
            $batch = MaterialBatch::query()->create(['material_id' => $material->id, 'lot_number' => $row['batch_number'], 'received_date' => now()->toDateString(), 'expiry_date' => $row['expiry_date'] ?: null, 'quantity_received' => $row['quantity'], 'quantity_available' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null]);
            $movement = MaterialStockMovement::query()->create(['material_id' => $material->id, 'material_batch_id' => $batch->id, 'unit_id' => $material->base_unit_id, 'movement_type' => 'opening_balance', 'quantity' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'created_by' => $actor->id, 'occurred_at' => now(), 'notes' => 'Opening balance import.']);

            return [
                ['model' => MaterialStockMovement::class, 'id' => $movement->id, 'action' => 'created'],
                ['model' => MaterialBatch::class, 'id' => $batch->id, 'action' => 'created'],
            ];
        }

        $variant = ProductVariant::query()->where('sku', $row['sku_or_name'])->orWhere('name', $row['sku_or_name'])->firstOrFail();
        $batch = FinishedProductBatch::query()->create(['product_variant_id' => $variant->id, 'batch_number' => $row['batch_number'], 'production_date' => now()->toDateString(), 'expiry_date' => $row['expiry_date'] ?: null, 'quantity_received' => $row['quantity'], 'quantity_available' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'status' => 'available']);
        $movement = FinishedStockMovement::query()->create(['product_variant_id' => $variant->id, 'finished_product_batch_id' => $batch->id, 'movement_type' => 'opening_balance', 'quantity' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'created_by' => $actor->id, 'occurred_at' => now(), 'notes' => 'Opening balance import.']);

        return [
            ['model' => FinishedStockMovement::class, 'id' => $movement->id, 'action' => 'created'],
            ['model' => FinishedProductBatch::class, 'id' => $batch->id, 'action' => 'created'],
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function applyOpeningCredit(array $row, User $actor): array
    {
        $customer = Customer::query()->whereRaw('LOWER(name) = ?', [strtolower($row['customer_name'])])->firstOrFail();
        $saleType = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $sale = Sale::query()->create(['sale_number' => 'OPEN-CREDIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)), 'customer_id' => $customer->id, 'sale_type_id' => $saleType->id, 'created_by' => $actor->id, 'sale_date' => now(), 'subtotal' => $row['amount'], 'total' => $row['amount'], 'balance_due' => $row['amount'], 'status' => 'completed', 'payment_status' => 'unpaid', 'notes' => 'Opening credit balance import.']);
        CreditInvoice::query()->create(['invoice_number' => 'OPEN-CR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)), 'sale_id' => $sale->id, 'customer_id' => $customer->id, 'issue_date' => now()->toDateString(), 'due_date' => $row['due_date'], 'total' => $row['amount'], 'balance' => $row['amount'], 'status' => 'not_due']);
        $customer->increment('outstanding_balance', (float) $row['amount']);

        return ['model' => Sale::class, 'id' => $sale->id, 'action' => 'created'];
    }

    private function parseBoolean(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'yes', 'true'], true);
    }
}
