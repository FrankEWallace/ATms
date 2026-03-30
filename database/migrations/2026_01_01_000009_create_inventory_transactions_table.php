<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('inventory_item_id', 36);
            $table->char('site_id', 36);
            $table->decimal('quantity_change', 15, 4);
            $table->string('reason')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
