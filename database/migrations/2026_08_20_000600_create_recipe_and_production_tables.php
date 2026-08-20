<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('expected_output_quantity', 12, 3)->nullable();
            $table->foreignId('output_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'version']);
            $table->index(['product_variant_id', 'status']);
        });

        Schema::create('recipe_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->string('usage_type', 30)->default('ingredient')->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('wastage_allowance_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['recipe_id', 'material_id', 'usage_type']);
        });

        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->date('planned_date')->index();
            $table->decimal('planned_quantity', 12, 3);
            $table->string('status', 20)->default('planned')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->date('production_date')->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('planned_quantity', 12, 3);
            $table->decimal('actual_output_quantity', 12, 3)->nullable();
            $table->decimal('rejected_quantity', 12, 3)->default(0);
            $table->decimal('wastage_quantity', 12, 3)->default(0);
            $table->decimal('yield_percent', 7, 2)->nullable();
            $table->decimal('estimated_batch_cost', 14, 2)->nullable();
            $table->decimal('estimated_unit_cost', 14, 4)->nullable();
            $table->text('notes')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('production_material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->string('usage_type', 30)->default('ingredient')->index();
            $table->decimal('planned_quantity', 12, 3);
            $table->decimal('actual_quantity', 12, 3);
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->decimal('line_cost', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->decimal('accepted_quantity', 12, 3);
            $table->decimal('rejected_quantity', 12, 3)->default(0);
            $table->decimal('estimated_unit_cost', 14, 4)->nullable();
            $table->string('status', 20)->default('pending_inventory')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('production_material_usages');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('production_plans');
        Schema::dropIfExists('recipe_materials');
        Schema::dropIfExists('recipes');
    }
};
