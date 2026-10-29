<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ยกเลิกเอกสาร (void) แทนการลบ
 *
 * เอกสารทางบัญชี (ใบส่งของ / ใบแจ้งหนี้ / ใบเสร็จ) ต้องเก็บไว้ครบทุกเลขที่
 * กด "ยกเลิก" แล้วเอกสารยังอยู่ แค่มีวันที่ยกเลิก ผู้ยกเลิก และเหตุผล
 * เอกสารที่ยกเลิกแล้วไม่ถูกนับในยอดใด ๆ (Model ใช้ cancelled_at แทน deleted_at ของ SoftDeletes)
 */
return new class extends Migration
{
    private const TABLES = ['delivery_notes', 'invoices', 'receipts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('cancelled_at')->nullable()->index();
                $t->foreignId('cancelled_by_user_id')->nullable()
                  ->constrained('users')->nullOnDelete();
                $t->string('cancel_reason', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('cancelled_by_user_id');
                $t->dropIndex(['cancelled_at']);
                $t->dropColumn(['cancelled_at', 'cancel_reason']);
            });
        }
    }
};
