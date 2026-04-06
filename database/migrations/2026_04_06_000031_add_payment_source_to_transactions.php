<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'payment' as a valid transaction source.
     *
     * The source column is a varchar(20) with no check constraint (SQLite-compatible).
     * On PostgreSQL you may optionally enforce: CHECK (source IN ('manual','inventory','order','payment'))
     *
     * Sources:
     *   payment   — income recorded via "Record Payment" action (linked to customer)
     *   inventory — expense auto-created when inventory is consumed
     *   order     — transaction auto-created from an order
     *   manual    — manually entered expense via "Record Expense" action
     */
    public function up(): void
    {
        // Update the column comment on databases that support it (PostgreSQL / MySQL).
        // SQLite ignores this gracefully via the try/catch.
        try {
            DB::statement("
                COMMENT ON COLUMN transactions.source IS
                'manual | inventory | order | payment'
            ");
        } catch (\Throwable) {
            // SQLite does not support column comments — safe to skip.
        }
    }

    public function down(): void
    {
        try {
            DB::statement("
                COMMENT ON COLUMN transactions.source IS
                'manual | inventory | order'
            ");
        } catch (\Throwable) {
            // no-op on SQLite
        }
    }
};
