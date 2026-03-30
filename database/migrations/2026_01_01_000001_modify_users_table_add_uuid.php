<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraints that reference users.id from personal_access_tokens
        // then alter the users table to use UUID primary key
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // tokenable_id is a morph column, may need special handling
        });

        Schema::table('users', function (Blueprint $table) {
            // Remove auto-increment and change to char(36) UUID
            $table->char('id', 36)->change();
        });

        // personal_access_tokens uses morphs so tokenable_id is also a string
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id', 36)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigIncrements('id')->change();
        });
    }
};
