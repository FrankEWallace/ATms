<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('org_id', 36);
            $table->string('name');
            $table->string('metric');
            $table->enum('condition', ['gt', 'lt', 'eq']);
            $table->decimal('threshold', 15, 4);
            $table->string('notify_email');
            $table->boolean('enabled')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
