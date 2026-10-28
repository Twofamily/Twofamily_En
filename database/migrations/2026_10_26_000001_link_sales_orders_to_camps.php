<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แคมป์แบบ C: 1 แคมป์ มีใบสั่งขายได้หลายใบ
 *
 * เดิม:  camps.id_so → sales_orders     (1 ใบสั่งขาย = 1 แคมป์)
 * ใหม่:  sales_orders.id_camp → camps   (ลูกค้าสั่งเพิ่มกลางงาน = เปิดใบสั่งขายใหม่ในแคมป์เดิม)
 *
 * camps.id_so ยังเก็บไว้ก่อน ไม่ลบ เพื่อให้ย้อนกลับได้
 * โค้ดใหม่ไม่อ่าน/ไม่เขียนคอลัมน์นั้นแล้ว จะลบทิ้งในเฟส 7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // camps.id_camp เป็น bigIncrements → ต้องใช้ unsignedBigInteger
            $table->unsignedBigInteger('id_camp')->nullable()->after('id_customer');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->restrictOnDelete();   // มีใบสั่งขายอยู่ในแคมป์ ห้ามลบแคมป์

            $table->index('id_camp');
        });

        // ย้ายความสัมพันธ์เดิมมาฝั่งใบสั่งขาย
        DB::statement('
            UPDATE sales_orders so
            JOIN camps c ON c.id_so = so.id_so
            SET so.id_camp = c.id_camp
            WHERE so.id_camp IS NULL
        ');

        // ใบสั่งขายที่อยู่ในแคมป์แล้ว แต่สถานะยังค้างที่ "ยืนยันแล้ว" → ถือว่ากำลังดำเนินงาน
        DB::table('sales_orders')
            ->whereNotNull('id_camp')
            ->where('status', 'confirmed')
            ->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['id_camp']);
            $table->dropIndex(['id_camp']);
            $table->dropColumn('id_camp');
        });
    }
};
