<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REMOVED_SLUGS = [
        'broadcasts.view',
        'broadcasts.send',
        'broadcasts.manage_templates',
        'broadcasts.manage_consent',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('permissions')->whereIn('slug', self::REMOVED_SLUGS)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally not restored: these permissions backed removed
        // broadcast features (list/detail pages, templates, consent
        // management) that no longer exist in the application.
    }
};
