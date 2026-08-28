<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const REMOVED_ROLES = [
        'Manager',
        'Cashier / Sales Officer',
        'Inventory Officer',
        'Production Officer',
        'Purchasing Officer',
        'Accounts Officer',
        'Marketing Officer',
        'Auditor',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The existing owner role becomes "CEO" in place, so whoever already
        // holds unrestricted access keeps it under the new name.
        DB::table('roles')->where('is_owner', true)->update([
            'name' => 'CEO',
            'slug' => 'ceo',
            'description' => 'Unrestricted system owner access.',
            'updated_at' => now(),
        ]);

        foreach (['COO', 'Supervisor'] as $name) {
            DB::table('roles')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => null,
                    'is_system' => true,
                    'is_owner' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('roles')->whereIn('name', self::REMOVED_ROLES)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('slug', 'ceo')->update([
            'name' => 'Owner / Super Admin',
            'slug' => 'owner-super-admin',
            'description' => 'Unrestricted system owner access.',
            'updated_at' => now(),
        ]);

        DB::table('roles')->whereIn('slug', ['coo', 'supervisor'])->delete();
    }
};
