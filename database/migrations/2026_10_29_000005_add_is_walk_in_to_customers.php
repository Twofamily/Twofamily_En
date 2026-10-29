<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ลูกค้าทั่วไป: เลิกดูจากชื่อ → ใช้คอลัมน์ is_walk_in
 *
 * 1. เพิ่มคอลัมน์ is_walk_in
 * 2. แถวลูกค้าทั่วไปที่มีอยู่ (ชื่อเก่า/ชื่อใหม่ รวมที่ถูก soft delete) → เก็บแถวแรก (id น้อยสุด) เป็นตัวจริง
 * 3. แถวที่ซ้ำ → ย้ายเอกสารทุกตารางที่มี id_customer มาที่ตัวจริง แล้วลบแถวซ้ำทิ้ง
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'is_walk_in')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('is_walk_in')->default(false)->after('customer_type')->index();
            });
        }

        $ids = DB::table('customers')
            ->whereIn('name_customer', [Customer::WALK_IN_NAME, Customer::WALK_IN_OLD_NAME])
            ->orderBy('id_customer')
            ->pluck('id_customer');

        if ($ids->isEmpty()) {
            return;   // ยังไม่เคยใช้ลูกค้าทั่วไป — Customer::walkIn() จะสร้างให้เองตอนใช้ครั้งแรก
        }

        $keep       = $ids->first();
        $duplicates = $ids->slice(1)->values();

        // ตารางที่อ้างถึงลูกค้า (มีคอลัมน์ id_customer) — ข้ามตารางที่ไม่มีในฐานข้อมูลนี้
        $tables = collect(['quotations', 'sales_orders', 'camps', 'delivery_notes', 'invoices', 'receipts', 'payments'])
            ->filter(fn ($t) => Schema::hasTable($t) && Schema::hasColumn($t, 'id_customer'));

        DB::transaction(function () use ($keep, $duplicates, $tables) {
            DB::table('customers')->where('id_customer', $keep)->update([
                'is_walk_in'    => true,
                'name_customer' => Customer::WALK_IN_NAME,
                'deleted_at'    => null,
            ]);

            if ($duplicates->isEmpty()) {
                return;
            }

            foreach ($tables as $table) {
                DB::table($table)->whereIn('id_customer', $duplicates)->update(['id_customer' => $keep]);
            }

            DB::table('customers')->whereIn('id_customer', $duplicates)->delete();
        });
    }

    /** ย้อนได้แค่ลบคอลัมน์ — แถวที่รวมไปแล้วไม่แยกคืน (เป็นแถวของระบบที่ซ้ำโดยไม่ตั้งใจ) */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'is_walk_in')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['is_walk_in']);
                $table->dropColumn('is_walk_in');
            });
        }
    }
};
