<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม "วันที่ออกใบแจ้งหนี้" เป็นคอลัมน์จริง
 *
 * เดิมใบแจ้งหนี้ไม่มีวันที่ของตัวเอง ใช้ created_at แทน ซึ่งเป็นแค่เวลาที่บันทึกลงระบบ
 * ถ้าออกเอกสารย้อนหลังหรือบันทึกช้า วันที่บนใบกำกับภาษีจะผิด
 * จึงเก็บแยกไว้ เหมือนเอกสารตัวอื่น (date_quot, order_date, delivery_date, date_receipt)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) เพิ่มแบบ nullable ก่อน เพื่อให้ตารางที่มีข้อมูลอยู่แล้วเพิ่มคอลัมน์ได้
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('status');
        });

        // 2) ใบแจ้งหนี้เก่า → ใช้วันที่ที่บันทึกลงระบบ (ถ้าไม่มี ใช้วันนี้)
        DB::table('invoices')
            ->whereNull('invoice_date')
            ->update(['invoice_date' => DB::raw('DATE(COALESCE(created_at, CURRENT_DATE))')]);

        // 3) ทุกแถวมีค่าแล้ว → บังคับว่าต้องมีวันที่เสมอ + ทำ index ไว้สำหรับกรองช่วงวันที่
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable(false)->change();
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_date']);
            $table->dropColumn('invoice_date');
        });
    }
};
