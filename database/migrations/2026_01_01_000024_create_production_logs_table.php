<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_logs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('site_id', 36);
            $table->date('log_date');
            $table->decimal('ore_tonnes', 15, 4)->nullable();
            $table->decimal('waste_tonnes', 15, 4)->nullable();
            $table->decimal('grade_g_t', 10, 4)->nullable();
            $table->decimal('water_m3', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['site_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
