<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_number')->unique();
            $table->string('import_type', 60)->index();
            $table->string('status', 30)->default('dry_run')->index();
            $table->boolean('dry_run')->default(true);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('summary')->nullable();
            $table->json('rollback_metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_errors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->text('message');
            $table->json('row_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('import_batches');
    }
};
