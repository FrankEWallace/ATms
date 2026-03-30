<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('site_id', 36);
            $table->string('reference_no')->nullable();
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->enum('type', ['income', 'expense', 'refund']);
            $table->enum('status', ['success', 'pending', 'refunded', 'cancelled'])->default('pending');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('transaction_date');
            $table->char('created_by', 36)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
