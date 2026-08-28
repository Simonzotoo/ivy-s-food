<?php

namespace Tests\Feature;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\Supplier;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\CustomerFoundationSeeder;
use Database\Seeders\MaterialFoundationSeeder;
use Database\Seeders\ProductCatalogueSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Module12ImportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingsSeeder::class);
        $this->seed(ProductCatalogueSeeder::class);
        $this->seed(MaterialFoundationSeeder::class);
        $this->seed(CustomerFoundationSeeder::class);
    }

    public function test_import_permissions_are_enforced(): void
    {
        $this->get('/imports')->assertRedirect(route('login'));

        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $inventory = $this->userWithRole('Inventory Officer');

        $this->actingAs($cashier)->get('/imports')->assertForbidden();
        $this->actingAs($inventory)->get('/imports')->assertOk();
        $this->actingAs($cashier)->get('/imports/create/customers')->assertForbidden();
    }

    public function test_template_download_shows_headings_sample_and_notes(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');

        $this->actingAs($accounts)->get('/imports/templates/customers')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertSee('customer_type', false)
            ->assertSee('EXAMPLE', false)
            ->assertSee('Accepted customer types', false);
    }

    public function test_customer_csv_upload_maps_validates_and_commits(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = $this->csvFile("name,phone,customer_type,sale_type,email\nAma Mensah Shop,0244000111,Shop,Retail,ama@example.com\n");

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file])
            ->assertRedirect();

        $batch = ImportBatch::query()->firstOrFail();
        $this->assertSame('uploaded', $batch->status);
        $this->assertSame('customers', $batch->import_type);
        $this->assertNotNull($batch->checksum);

        $this->actingAs($accounts)->get(route('imports.map', $batch))->assertOk()->assertSee('Match your file');

        $mapping = ['name' => 'name', 'phone' => 'phone', 'customer_type' => 'customer_type', 'sale_type' => 'sale_type', 'location' => null, 'address' => null, 'email' => 'email', 'credit_allowed' => null, 'credit_limit' => null, 'notes' => null, 'communication_consent' => null, 'status' => null];
        $this->actingAs($accounts)->post(route('imports.map.store', $batch), ['mapping' => $mapping, 'duplicate_strategy' => 'stop'])
            ->assertRedirect(route('imports.show', $batch));

        $batch->refresh();
        $this->assertSame('previewed', $batch->status);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertSame(1, $batch->new_rows);
        $this->assertSame(0, $batch->error_rows);

        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();

        $batch->refresh();
        $this->assertSame('applied', $batch->status);
        $this->assertDatabaseHas('customers', ['name' => 'Ama Mensah Shop', 'normalized_phone' => '+233244000111']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'import.committed']);
    }

    public function test_customer_xlsx_upload_is_read_correctly(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = $this->xlsxFile(['name', 'customer_type'], [['XLSX Customer', 'Individual']]);

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file])->assertRedirect();
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type'],
            'duplicate_strategy' => 'stop',
        ])->assertRedirect();

        $batch->refresh();
        $this->assertSame(1, $batch->new_rows);

        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();
        $this->assertDatabaseHas('customers', ['name' => 'XLSX Customer']);
    }

    public function test_invalid_rows_are_reported_and_not_committed(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = $this->csvFile("name,customer_type,email\n,Unknown Type,not-an-email\n");

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type', 'email' => 'email'],
            'duplicate_strategy' => 'stop',
        ]);

        $batch->refresh();
        $this->assertSame(1, $batch->error_rows);
        $this->assertSame(0, $batch->valid_rows);
        $this->assertSame(0, Customer::query()->count());

        $this->actingAs($accounts)->get(route('imports.errors', $batch))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_duplicate_phone_after_normalization_is_detected_and_stop_blocks_commit(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        Customer::query()->create([
            'customer_type_id' => CustomerType::query()->firstOrFail()->id,
            'name' => 'Existing Customer',
            'phone' => '0244000111',
            'normalized_phone' => '+233244000111',
            'status' => 'active',
        ]);

        $file = $this->csvFile("name,customer_type,phone\nSame Person Re-entered,Individual,+233 24 400 0111\n");
        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();

        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type', 'phone' => 'phone'],
            'duplicate_strategy' => 'stop',
        ]);

        $batch->refresh();
        $this->assertSame(1, $batch->duplicate_rows);

        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertSessionHasErrors('batch');
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_skip_strategy_leaves_existing_record_untouched(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->firstOrFail()->id,
            'name' => 'Original Name',
            'phone' => '0244000111',
            'normalized_phone' => '+233244000111',
            'status' => 'active',
        ]);

        $file = $this->csvFile("name,customer_type,phone\nOverwritten Name,Individual,0244000111\n");
        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type', 'phone' => 'phone'],
            'duplicate_strategy' => 'skip',
        ]);
        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();

        $this->assertSame('Original Name', $customer->fresh()->name);
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_update_strategy_updates_matching_record(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->firstOrFail()->id,
            'name' => 'Original Name',
            'phone' => '0244000111',
            'normalized_phone' => '+233244000111',
            'status' => 'active',
        ]);

        $file = $this->csvFile("name,customer_type,phone,location\nUpdated Name,Individual,0244000111,Accra\n");
        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type', 'phone' => 'phone', 'location' => 'location'],
            'duplicate_strategy' => 'update',
        ]);

        $batch->refresh();
        $this->assertSame(1, $batch->update_rows);

        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();

        $this->assertSame('Updated Name', $customer->fresh()->name);
        $this->assertSame('Accra', $customer->fresh()->location);
        $this->assertSame(1, Customer::query()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'customer.updated']);
    }

    public function test_rollback_removes_created_customer_but_retains_one_with_a_sale(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = $this->csvFile("name,customer_type,phone\nRemovable Customer,Individual,0201111111\nKeeper Customer,Individual,0201111112\n");

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type', 'phone' => 'phone'],
            'duplicate_strategy' => 'stop',
        ]);
        $this->actingAs($accounts)->post(route('imports.commit', $batch), ['confirm' => '1']);

        $keeper = Customer::query()->where('name', 'Keeper Customer')->firstOrFail();
        Sale::query()->create([
            'sale_number' => 'KEEP-SALE-1',
            'customer_id' => $keeper->id,
            'sale_type_id' => SaleType::query()->where('name', 'Retail')->firstOrFail()->id,
            'created_by' => $accounts->id,
            'sale_date' => now(),
            'subtotal' => '10.00',
            'total' => '10.00',
            'balance_due' => '0.00',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $manager = $this->userWithRole('Manager');
        $this->actingAs($manager)->post(route('imports.rollback', $batch->fresh()))->assertRedirect();

        $this->assertSoftDeleted('customers', ['name' => 'Removable Customer']);
        $this->assertDatabaseHas('customers', ['name' => 'Keeper Customer', 'deleted_at' => null]);
        $this->assertSame('rolled_back_partial', $batch->fresh()->status);
    }

    public function test_only_authorized_role_can_commit_customer_import(): void
    {
        // Inventory Officer can upload and validate any type, but customer imports are an
        // Accounts Officer / Manager responsibility - only the commit step is blocked.
        $inventory = $this->userWithRole('Inventory Officer');
        $file = $this->csvFile("name,customer_type\nSomeone,Individual\n");

        $this->actingAs($inventory)->post('/imports', ['import_type' => 'customers', 'file' => $file])->assertRedirect();
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($inventory)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type'],
            'duplicate_strategy' => 'stop',
        ])->assertRedirect();

        $this->actingAs($inventory)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertForbidden();
        $this->assertSame(0, Customer::query()->count());
    }

    public function test_disguised_file_extension_is_rejected(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = UploadedFile::fake()->createWithContent('fake.xlsx', 'not a real zip file');

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_oversized_file_is_rejected(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = UploadedFile::fake()->create('big.csv', 6000, 'text/csv');

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_error_download_neutralizes_formula_injection(): void
    {
        $accounts = $this->userWithRole('Accounts Officer');
        $file = $this->csvFile("name,customer_type\n=cmd|'/c calc'!A1,BadType\n");

        $this->actingAs($accounts)->post('/imports', ['import_type' => 'customers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($accounts)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'customer_type' => 'customer_type'],
            'duplicate_strategy' => 'stop',
        ]);

        $response = $this->actingAs($accounts)->get(route('imports.errors', $batch));
        $response->assertOk();
        $this->assertStringNotContainsString('"=cmd', $response->getContent());
        $this->assertStringContainsString("'=cmd", $response->getContent());
    }

    public function test_supplier_import_works_through_the_new_engine(): void
    {
        $purchasing = $this->userWithRole('Purchasing Officer');

        $file = $this->csvFile("name,phone,location,email,address,status\nKumasi Fruit Suppliers,0244000222,Kumasi,sales@example.com,Kejetia,active\n");
        $this->actingAs($purchasing)->post('/imports', ['import_type' => 'suppliers', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($purchasing)->post(route('imports.map.store', $batch), [
            'mapping' => ['name' => 'name', 'phone' => 'phone', 'location' => 'location', 'email' => 'email', 'address' => 'address', 'status' => 'status'],
        ]);
        $this->actingAs($purchasing)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'Kumasi Fruit Suppliers', 'location' => 'Kumasi']);
        $this->assertSame(1, Supplier::query()->count());
    }

    public function test_opening_stock_import_creates_finished_batch_and_movement_not_direct_balance_edit(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $variant = ProductVariant::query()->firstOrFail();
        $variant->update(['sku' => 'FIN-001']);

        $file = $this->csvFile("stock_type,sku_or_name,batch_number,quantity,unit_cost,expiry_date\nfinished,FIN-001,OPEN-BATCH,5,2.50,2026-12-31\n");
        $this->actingAs($inventory)->post('/imports', ['import_type' => 'opening_stock', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($inventory)->post(route('imports.map.store', $batch), [
            'mapping' => ['stock_type' => 'stock_type', 'sku_or_name' => 'sku_or_name', 'batch_number' => 'batch_number', 'quantity' => 'quantity', 'unit_cost' => 'unit_cost', 'expiry_date' => 'expiry_date'],
        ]);
        $this->actingAs($inventory)->post(route('imports.commit', $batch), ['confirm' => '1'])->assertRedirect();

        $stockBatch = FinishedProductBatch::query()->where('batch_number', 'OPEN-BATCH')->firstOrFail();
        $this->assertSame('5.000', (string) $stockBatch->quantity_available);
        $this->assertDatabaseHas('finished_stock_movements', ['movement_type' => 'opening_balance']);

        $manager = $this->userWithRole('Manager');
        $this->actingAs($manager)->post(route('imports.rollback', $batch->fresh()))->assertRedirect();
        $this->assertSame(0, FinishedProductBatch::query()->count());
        $this->assertSame(0, FinishedStockMovement::query()->count());
    }

    public function test_raw_material_and_open_credit_imports_are_applied_safely(): void
    {
        $inventory = $this->userWithRole('Inventory Officer');
        $accounts = $this->userWithRole('Accounts Officer');
        $unit = MeasurementUnit::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Kilogram', 'type' => 'weight']);
        $category = MaterialCategory::query()->firstOrCreate(['slug' => 'fruit'], ['name' => 'Fruit', 'type' => 'raw_material']);
        Material::query()->create(['name' => 'Imported Pineapple', 'sku' => 'RAW-PINE', 'material_category_id' => $category->id, 'base_unit_id' => $unit->id]);
        $customer = Customer::query()->create([
            'customer_type_id' => CustomerType::query()->firstOrFail()->id,
            'name' => 'Opening Credit Customer',
            'status' => 'active',
        ]);

        $file = $this->csvFile("stock_type,sku_or_name,batch_number,quantity,unit_cost,expiry_date\nraw,RAW-PINE,RAW-OPEN,12,1.25,\n");
        $this->actingAs($inventory)->post('/imports', ['import_type' => 'opening_stock', 'file' => $file]);
        $batch = ImportBatch::query()->firstOrFail();
        $this->actingAs($inventory)->post(route('imports.map.store', $batch), [
            'mapping' => ['stock_type' => 'stock_type', 'sku_or_name' => 'sku_or_name', 'batch_number' => 'batch_number', 'quantity' => 'quantity', 'unit_cost' => 'unit_cost', 'expiry_date' => 'expiry_date'],
        ]);
        $this->actingAs($inventory)->post(route('imports.commit', $batch), ['confirm' => '1']);
        $this->assertDatabaseHas('material_stock_movements', ['movement_type' => 'opening_balance']);

        $creditFile = $this->csvFile("customer_name,amount,due_date\nOpening Credit Customer,30.00,2026-09-20\n");
        $this->actingAs($accounts)->post('/imports', ['import_type' => 'open_credit_balances', 'file' => $creditFile]);
        $creditBatch = ImportBatch::query()->where('import_type', 'open_credit_balances')->firstOrFail();
        $this->actingAs($accounts)->post(route('imports.map.store', $creditBatch), [
            'mapping' => ['customer_name' => 'customer_name', 'amount' => 'amount', 'due_date' => 'due_date'],
        ]);
        $this->actingAs($accounts)->post(route('imports.commit', $creditBatch), ['confirm' => '1'])->assertRedirect();

        $this->assertSame(1, CreditInvoice::query()->count());
        $this->assertSame('30.00', (string) $customer->fresh()->outstanding_balance);
    }

    public function test_module_twelve_permission_seeder_is_idempotent(): void
    {
        $count = Role::query()->withCount('permissions')->get()->sum('permissions_count');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertSame($count, Role::query()->withCount('permissions')->get()->sum('permissions_count'));
    }

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $content);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function xlsxFile(array $headers, array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        $strings = array_values(array_unique([...$headers, ...array_merge(...$rows)]));
        $index = array_flip($strings);

        $sst = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .implode('', array_map(fn ($s) => '<si><t>'.htmlspecialchars($s, ENT_XML1).'</t></si>', $strings))
            .'</sst>';

        $colLetter = fn (int $i): string => chr(65 + $i);
        $rowsXml = '<row r="1">'.implode('', array_map(fn ($h, $i) => '<c r="'.$colLetter($i).'1" t="s"><v>'.$index[$h].'</v></c>', $headers, array_keys($headers))).'</row>';
        foreach ($rows as $r => $row) {
            $rowsXml .= '<row r="'.($r + 2).'">'.implode('', array_map(fn ($v, $i) => '<c r="'.$colLetter($i).($r + 2).'" t="s"><v>'.$index[$v].'</v></c>', $row, array_keys($row))).'</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$rowsXml.'</sheetData></worksheet>';

        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', $sst);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
