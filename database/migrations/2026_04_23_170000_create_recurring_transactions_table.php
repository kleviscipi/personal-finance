<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->enum('type', ['expense', 'income', 'transfer'])->default('expense');
            $table->decimal('amount', 19, 4);
            $table->string('currency', 3);
            $table->date('next_run_date');
            $table->date('end_date')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->unsignedSmallInteger('interval')->default(1);
            $table->unsignedTinyInteger('anchor_day')->nullable();
            $table->unsignedTinyInteger('anchor_month')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subcategory_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active', 'next_run_date']);
            $table->index(['account_id', 'frequency']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
