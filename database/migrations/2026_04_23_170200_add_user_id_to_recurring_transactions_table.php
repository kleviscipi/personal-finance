<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('created_by')
                ->constrained()
                ->nullOnDelete();
            $table->index(['user_id', 'account_id']);
        });

        DB::table('recurring_transactions')
            ->whereNull('user_id')
            ->update([
                'user_id' => DB::raw('created_by'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'account_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
