<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('default_sale_type_id')->nullable()->constrained('sale_types')->nullOnDelete();
            $table->string('name')->index();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->boolean('communication_consent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_type_id', 'status']);
            $table->index(['default_sale_type_id', 'status']);
        });

        Schema::create('customer_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('direction', 20)->default('outbound');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('logged')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_communications');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('sale_types');
        Schema::dropIfExists('customer_types');
    }
};
