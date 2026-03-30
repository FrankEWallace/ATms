<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('org_id', 36);
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['active', 'inactive', 'decommissioned'])->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
