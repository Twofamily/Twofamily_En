<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * บันทึกผู้ออกเอกสาร (ผู้ใช้ที่กดสร้าง) ของใบสั่งขาย / ใบส่งของ / ใบแจ้งหนี้
 * ใช้แสดงชื่อ + ลายเซ็นในช่อง "ในนามบริษัท" ของ PDF
 * (ใบเสนอราคาและใบเสร็จมี issued_by_user_id อยู่แล้ว)
 *
 * เอกสารเก่าที่ออกก่อนหน้านี้ไม่รู้ว่าใครออก → ว่างไว้ ช่องลายเซ็นจะเว้นว่างให้เซ็นมือ
 */
return new class extends Migration
{
    private const TABLES = ['sales_orders', 'delivery_notes', 'invoices'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('created_by_user_id')->nullable()
                  ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('created_by_user_id');
            });
        }
    }
};
