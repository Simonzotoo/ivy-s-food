<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->unique();
            $table->date('production_date')->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('quantity_received', 12, 3);
            $table->decimal('quantity_available', 12, 3)->default(0);
            $table->decimal('quantity_reserved', 12, 3)->default(0);
            $table->decimal('quantity_damaged', 12, 3)->default(0);
            $table->decimal('quantity_expired', 12, 3)->default(0);
            $table->decimal('quantity_quarantined', 12, 3)->default(0);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();

            $table->index(['product_variant_id', 'status', 'expiry_date']);
        });

        Schema::create('finished_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('finished_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 40)->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('stock_type', 20)->index();
            $table->foreignId('material_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('finished_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('adjustment_type', 40)->index();
            $table->decimal('quantity', 12, 3);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('stock_type', 20)->index();
            $table->date('stocktake_date')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('finished_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3);
            $table->decimal('variance_quantity', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocktake_items');
        Schema::dropIfExists('stocktakes');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('finished_stock_movements');
        Schema::dropIfExists('finished_product_batches');
    }
};
