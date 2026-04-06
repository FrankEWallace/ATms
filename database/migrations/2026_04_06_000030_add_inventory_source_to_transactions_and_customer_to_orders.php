<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('inventory_item_id')->nullable()->after('expense_category_id');
            $table->string('source', 20)->nullable()->after('inventory_item_id')
                  ->comment('manual | inventory | order');

            $table->foreign('inventory_item_id')
                  ->references('id')->on('inventory_items')
                  ->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable()->after('channel_id');

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn(['inventory_item_id', 'source']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
