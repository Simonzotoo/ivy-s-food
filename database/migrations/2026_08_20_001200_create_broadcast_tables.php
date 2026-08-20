<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->timestamp('communication_opted_out_at')->nullable()->after('communication_consent');
            $table->string('communication_opt_out_reason')->nullable()->after('communication_opted_out_at');
        });

        Schema::create('broadcast_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('channel', 30)->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->string('broadcast_number')->unique();
            $table->foreignId('broadcast_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('channel', 30)->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 30)->default('draft')->index();
            $table->json('filters')->nullable();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('total_logged')->default(0);
            $table->unsignedInteger('total_skipped')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('broadcast_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('skip_reason')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['broadcast_id', 'customer_id']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_recipients');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('broadcast_templates');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['communication_opted_out_at', 'communication_opt_out_reason']);
        });
    }
};
