<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remap any existing rows before the column definition changes.
        DB::table('purchase_orders')->where('status', 'signed')->update(['status' => 'supplier_acceptance']);
        DB::table('purchase_orders')->where('status', 'acknowledged')->update(['status' => 'delivery_inspection']);

        // MODIFY COLUMN avoids the duplicate-index issue caused by ->change() on MySQL.
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','for_signature','supplier_acceptance','delivery_inspection','completed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::table('purchase_orders')->where('status', 'supplier_acceptance')->update(['status' => 'signed']);
        DB::table('purchase_orders')->where('status', 'delivery_inspection')->update(['status' => 'acknowledged']);

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','for_signature','signed','acknowledged','completed') NOT NULL DEFAULT 'draft'");
    }
};
