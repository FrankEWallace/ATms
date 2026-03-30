<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('site_id', 36);
            $table->char('supplier_id', 36)->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
