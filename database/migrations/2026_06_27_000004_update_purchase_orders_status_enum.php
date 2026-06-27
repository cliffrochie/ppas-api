<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the two PO status values to match the design's 5-step stepper:
     *   signed        → supplier_acceptance
     *   acknowledged  → delivery_inspection
     *
     * SQLite (dev/test) stores ENUM columns as TEXT and does not enforce enum
     * constraints at the database level. App-layer validation (Form Request
     * `in:` rules) is the sole enforcement boundary for SQLite. No DDL change
     * is needed on that driver.
     *
     * MySQL (production) uses a real ENUM type, so we first migrate any
     * existing data and then redefine the column type.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Migrate existing rows before redefining the column.
            DB::statement("UPDATE purchase_orders SET status = 'supplier_acceptance' WHERE status = 'signed'");
            DB::statement("UPDATE purchase_orders SET status = 'delivery_inspection' WHERE status = 'acknowledged'");

            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','for_signature','supplier_acceptance','delivery_inspection','completed') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE purchase_orders SET status = 'signed' WHERE status = 'supplier_acceptance'");
            DB::statement("UPDATE purchase_orders SET status = 'acknowledged' WHERE status = 'delivery_inspection'");

            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','for_signature','signed','acknowledged','completed') NOT NULL DEFAULT 'draft'");
        }
    }
};
