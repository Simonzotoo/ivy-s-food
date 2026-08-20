<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('payment_method', 20)->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('unallocated_amount', 14, 2)->default(0);
            $table->string('momo_network')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->timestamp('received_at')->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['customer_payment_id', 'credit_invoice_id']);
        });

        Schema::create('reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('channel', 20);
            $table->string('trigger_status', 30)->index();
            $table->text('body');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('credit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('reminder_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('status', 30)->default('logged')->index();
            $table->text('message');
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_reminders');
        Schema::dropIfExists('reminder_templates');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('customer_payments');
    }
};
