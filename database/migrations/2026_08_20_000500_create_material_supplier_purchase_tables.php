<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 30)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->decimal('factor', 18, 6);
            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('base_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->string('name')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('track_lot')->default(true);
            $table->boolean('track_expiry')->default(false);
            $table->decimal('reorder_level', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('purchase_date');
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::create('purchase_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('material_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_number')->nullable()->index();
            $table->date('received_date');
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('quantity_received', 12, 3);
            $table->decimal('quantity_available', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();
        });

        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->string('movement_type', 40)->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->text('reason');
            $table->string('status', 20)->default('completed')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_batch_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('material_stock_movements');
        Schema::dropIfExists('material_batches');
        Schema::dropIfExists('purchase_attachments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('material_categories');
    }
};
