<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->string('original_filename')->nullable()->after('import_type');
            $table->string('file_disk', 40)->nullable()->after('original_filename');
            $table->string('file_path')->nullable()->after('file_disk');
            $table->string('checksum', 64)->nullable()->after('file_path');
            $table->json('mapping')->nullable()->after('checksum');
            $table->string('duplicate_strategy', 20)->nullable()->after('mapping');
            $table->unsignedInteger('duplicate_rows')->default(0)->after('error_rows');
            $table->unsignedInteger('new_rows')->default(0)->after('duplicate_rows');
            $table->unsignedInteger('update_rows')->default(0)->after('new_rows');
            $table->unsignedInteger('skipped_rows')->default(0)->after('update_rows');
            $table->timestamp('confirmed_at')->nullable()->after('applied_at');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropColumn([
                'original_filename',
                'file_disk',
                'file_path',
                'checksum',
                'mapping',
                'duplicate_strategy',
                'duplicate_rows',
                'new_rows',
                'update_rows',
                'skipped_rows',
                'confirmed_at',
            ]);
        });
    }
};
