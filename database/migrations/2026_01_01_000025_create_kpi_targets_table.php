<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('site_id', 36);
            $table->date('month'); // first of month
            $table->decimal('revenue_target', 15, 2)->nullable();
            $table->decimal('expense_budget', 15, 2)->nullable();
            $table->integer('shift_target')->nullable();
            $table->decimal('equipment_uptime_pct', 5, 2)->nullable();
            $table->decimal('ore_tonnes_target', 15, 4)->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['site_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
