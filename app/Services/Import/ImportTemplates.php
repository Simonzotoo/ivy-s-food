<?php

namespace App\Services\Import;

/**
 * Field specifications, sample rows and instructions for every supported import type.
 * Shared by template downloads, column-mapping suggestions and row validation so the
 * three stay in sync.
 */
class ImportTemplates
{
    /**
     * @return array<string, array{label: string, fields: array<string, array{label: string, required: bool}>, sample: array<string, string>, notes: list<string>}>
     */
    public static function specs(): array
    {
        return [
            'customers' => [
                'label' => 'Customers',
                'fields' => [
                    'name' => ['label' => 'Customer name', 'required' => true],
                    'phone' => ['label' => 'Phone number', 'required' => false],
                    'customer_type' => ['label' => 'Customer type', 'required' => true],
                    'sale_type' => ['label' => 'Default sales type', 'required' => false],
                    'location' => ['label' => 'Location', 'required' => false],
                    'address' => ['label' => 'Address', 'required' => false],
                    'email' => ['label' => 'Email', 'required' => false],
                    'credit_allowed' => ['label' => 'Credit allowed (Yes/No)', 'required' => false],
                    'credit_limit' => ['label' => 'Credit limit (GHS)', 'required' => false],
                    'notes' => ['label' => 'Notes', 'required' => false],
                    'communication_consent' => ['label' => 'Communication consent (Yes/No)', 'required' => false],
                    'status' => ['label' => 'Account status', 'required' => false],
                ],
                'sample' => [
                    'name' => 'EXAMPLE Ama Mensah Shop',
                    'phone' => '0244000111',
                    'customer_type' => 'Shop',
                    'sale_type' => 'Retail',
                    'location' => 'Kumasi',
                    'address' => 'Adum, near the market',
                    'email' => 'ama@example.com',
                    'credit_allowed' => 'No',
                    'credit_limit' => '',
                    'notes' => 'Prefers MoMo payments.',
                    'communication_consent' => 'Yes',
                    'status' => 'active',
                ],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'Customer name and Customer type are required. Everything else is optional.',
                    'Accepted customer types: Individual, Shop, Distributor, Mall / Supermarket, Organization.',
                    'Accepted default sales types: Retail, Wholesale, Distributor. Leave blank if unsure.',
                    'Phone numbers can be entered as 024xxxxxxx or +233xxxxxxxxx - they will be normalized automatically.',
                    'Credit allowed and Communication consent accept Yes/No, True/False or 1/0.',
                    'Credit limit is only used when Credit allowed is Yes, and only takes effect for staff with credit permission.',
                    'Account status accepts active, inactive or suspended. Leave blank for active.',
                    'Do not include an opening balance column here - opening credit balances are imported separately by an Accounts Officer.',
                ],
            ],
            'suppliers' => [
                'label' => 'Suppliers',
                'fields' => [
                    'name' => ['label' => 'Supplier name', 'required' => true],
                    'phone' => ['label' => 'Phone number', 'required' => false],
                    'location' => ['label' => 'Location', 'required' => false],
                    'email' => ['label' => 'Email', 'required' => false],
                    'address' => ['label' => 'Address', 'required' => false],
                    'status' => ['label' => 'Status', 'required' => false],
                ],
                'sample' => [
                    'name' => 'EXAMPLE Kumasi Fruit Suppliers',
                    'phone' => '0244000222',
                    'location' => 'Kumasi',
                    'email' => 'sales@example.com',
                    'address' => 'Kejetia, Kumasi',
                    'status' => 'active',
                ],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'Supplier name is required. Everything else is optional.',
                    'Status accepts active or inactive. Leave blank for active.',
                ],
            ],
            'raw_materials' => [
                'label' => 'Raw materials',
                'fields' => [
                    'name' => ['label' => 'Material name', 'required' => true],
                    'category' => ['label' => 'Category', 'required' => true],
                    'base_unit_symbol' => ['label' => 'Base unit (e.g. kg, L)', 'required' => true],
                    'sku' => ['label' => 'SKU', 'required' => false],
                    'reorder_level' => ['label' => 'Reorder level', 'required' => false],
                ],
                'sample' => [
                    'name' => 'EXAMPLE Fresh Pineapple',
                    'category' => 'Fruit',
                    'base_unit_symbol' => 'kg',
                    'sku' => 'RAW-PINE',
                    'reorder_level' => '50',
                ],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'Material name, Category and Base unit are required.',
                    'Base unit must be a unit symbol already configured in the system (for example kg, g, L, ml, pcs).',
                    'This creates or updates the material record only - it does not add stock. Use the Opening stock import to record starting quantities.',
                ],
            ],
            'product_variants' => [
                'label' => 'Product update (status)',
                'fields' => [
                    'sku' => ['label' => 'SKU', 'required' => true],
                    'name' => ['label' => 'Product name', 'required' => false],
                    'status' => ['label' => 'Status', 'required' => false],
                ],
                'sample' => ['sku' => 'PV-001', 'name' => 'Mango 500ml', 'status' => 'active'],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'SKU must already exist in the product catalogue - this updates existing products, it does not create new ones.',
                    'Status accepts draft, active or inactive.',
                ],
            ],
            'prices' => [
                'label' => 'Prices',
                'fields' => [
                    'sku' => ['label' => 'SKU', 'required' => true],
                    'retail_price' => ['label' => 'Retail price (GHS)', 'required' => false],
                    'wholesale_price' => ['label' => 'Wholesale price (GHS)', 'required' => false],
                    'distributor_price' => ['label' => 'Distributor price (GHS)', 'required' => false],
                ],
                'sample' => ['sku' => 'PV-001', 'retail_price' => '10.00', 'wholesale_price' => '9.00', 'distributor_price' => '8.00'],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'SKU must already exist in the product catalogue.',
                    'Leave a price column blank to leave that price unchanged.',
                    'Use plain numbers with up to two decimal places, for example 10.00 - do not include the currency symbol.',
                ],
            ],
            'opening_stock' => [
                'label' => 'Opening stock',
                'fields' => [
                    'stock_type' => ['label' => 'Stock type (raw/finished)', 'required' => true],
                    'sku_or_name' => ['label' => 'Material or product SKU/name', 'required' => true],
                    'batch_number' => ['label' => 'Batch/lot number', 'required' => true],
                    'quantity' => ['label' => 'Quantity', 'required' => true],
                    'unit_cost' => ['label' => 'Unit cost (GHS)', 'required' => false],
                    'expiry_date' => ['label' => 'Expiry date (YYYY-MM-DD)', 'required' => false],
                ],
                'sample' => ['stock_type' => 'raw', 'sku_or_name' => 'RAW-PINE', 'batch_number' => 'OPEN-0001', 'quantity' => '100', 'unit_cost' => '2.50', 'expiry_date' => '2026-12-31'],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'Stock type must be exactly "raw" or "finished".',
                    'This creates an auditable opening-balance stock movement - it never edits stock totals directly.',
                    'Requires opening-stock import permission, normally held by an Inventory Officer or Manager.',
                    'Dates must be in YYYY-MM-DD format, for example 2026-12-31.',
                ],
            ],
            'open_credit_balances' => [
                'label' => 'Open credit balances',
                'fields' => [
                    'customer_name' => ['label' => 'Customer name', 'required' => true],
                    'amount' => ['label' => 'Amount owed (GHS)', 'required' => true],
                    'due_date' => ['label' => 'Due date (YYYY-MM-DD)', 'required' => true],
                ],
                'sample' => ['customer_name' => 'EXAMPLE Ama Mensah Shop', 'amount' => '150.00', 'due_date' => '2026-09-20'],
                'notes' => [
                    'The first data row below the headings is an EXAMPLE only - replace or delete it before importing.',
                    'Customer name must exactly match an existing customer record.',
                    'This is a controlled reconciliation tool for starting balances only, separate from the normal customer import.',
                    'Requires opening-balance import permission, normally held by an Accounts Officer or Manager.',
                    'Dates must be in YYYY-MM-DD format, for example 2026-09-20.',
                ],
            ],
        ];
    }

    public static function label(string $type): string
    {
        return self::specs()[$type]['label'] ?? $type;
    }

    /**
     * @return array<string, array{label: string, required: bool}>
     */
    public static function fields(string $type): array
    {
        return self::specs()[$type]['fields'] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function requiredFields(string $type): array
    {
        return array_keys(array_filter(self::fields($type), fn (array $field): bool => $field['required']));
    }

    public static function csvTemplate(string $type): string
    {
        $spec = self::specs()[$type] ?? null;
        if ($spec === null) {
            return '';
        }

        $headers = array_keys($spec['fields']);
        $lines = [self::csvLine($headers)];
        $lines[] = self::csvLine(array_map(fn (string $key): string => (string) ($spec['sample'][$key] ?? ''), $headers));
        foreach ($spec['notes'] as $note) {
            $lines[] = self::csvLine(['# '.$note]);
        }

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Suggest a target-field => detected-header mapping using normalized exact/substring matches.
     *
     * @param  list<string>  $detectedHeaders
     * @return array<string, string|null>
     */
    public static function suggestMapping(string $type, array $detectedHeaders): array
    {
        $normalize = static fn (string $value): string => preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? '';
        $normalizedDetected = [];
        foreach ($detectedHeaders as $header) {
            $normalizedDetected[$normalize($header)] = $header;
        }

        $mapping = [];
        foreach (array_keys(self::fields($type)) as $field) {
            $normalizedField = $normalize($field);
            $mapping[$field] = $normalizedDetected[$normalizedField] ?? null;

            if ($mapping[$field] === null) {
                foreach ($normalizedDetected as $normalizedHeader => $original) {
                    if (str_contains($normalizedHeader, $normalizedField) || str_contains($normalizedField, $normalizedHeader)) {
                        $mapping[$field] = $original;
                        break;
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Neutralize CSV/formula injection: a cell that starts with =, +, -, @ or a tab/CR
     * is prefixed with a single quote so spreadsheet software never evaluates it as a formula.
     */
    public static function csvLine(array $values): string
    {
        $safe = array_map(static function ($value): string {
            $value = (string) $value;
            if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
                $value = "'".$value;
            }

            return '"'.str_replace('"', '""', $value).'"';
        }, $values);

        return implode(',', $safe);
    }
}
