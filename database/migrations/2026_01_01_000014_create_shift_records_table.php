<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_records', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('worker_id', 36);
            $table->char('site_id', 36);
            $table->date('shift_date');
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->decimal('output_metric', 15, 4)->nullable();
            $table->string('metric_unit')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_records');
    }
};
