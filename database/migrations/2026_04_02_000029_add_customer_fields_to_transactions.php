<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->char('customer_id', 36)->nullable()->after('site_id');
            $table->char('expense_category_id', 36)->nullable()->after('customer_id');

            $table->index('customer_id');
            $table->index('expense_category_id');

            $table->foreign('customer_id')
                ->references('id')->on('customers')->onDelete('set null');
            $table->foreign('expense_category_id')
                ->references('id')->on('expense_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['expense_category_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['expense_category_id']);
            $table->dropColumn(['customer_id', 'expense_category_id']);
        });
    }
};
