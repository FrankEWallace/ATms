<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('site_id', 36);
            $table->char('reported_by', 36)->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            // Using string instead of enum because "near-miss" contains a hyphen
            $table->string('type')->default('other');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('actions_taken')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_incidents');
    }
};
