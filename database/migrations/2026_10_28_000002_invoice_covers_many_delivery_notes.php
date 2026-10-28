<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ใบแจ้งหนี้ 1 ใบ รวมใบส่งของได้หลายใบ (วางบิลรายรอบ เช่น รายเดือน)
 *
 * เดิม:  invoices.id_delivery_note → ใบส่งของ 1 ใบ (1 ต่อ 1)
 * ใหม่:  delivery_notes.id_invoice → ใบแจ้งหนี้ที่เรียกเก็บใบส่งของนี้ (หลายใบส่งของ ต่อ 1 ใบแจ้งหนี้)
 *
 * invoices.id_delivery_note ยังเก็บไว้ ไม่ลบ เพื่อย้อนกลับได้ โค้ดใหม่ไม่เขียนคอลัมน์นี้แล้ว
 *
 * ใบแจ้งหนี้ทำหน้าที่ใบวางบิลด้วย จึงเพิ่ม
 *   credit_days        เครดิตเทอม (วัน)
 *   due_date           วันครบกำหนดชำระ = invoice_date + credit_days
 *   bill_received_date วันที่ลูกค้าเซ็นรับบิล
 *
 * และแก้ 2 จุดที่ขวางการรวมหลายใบ
 *   invoices.id_quotation      → nullable  (ใบสั่งขายที่ไม่ได้มาจากใบเสนอราคา)
 *   invoice_details.quantity   → decimal   (จำนวนคิวมีทศนิยม รวมหลายใบแล้วต้องไม่ถูกปัดทิ้ง)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_invoice')->nullable()->after('id_customer');

            $table->foreign('id_invoice')
                  ->references('id_invoice')->on('invoices')
                  ->nullOnDelete();   // ลบใบแจ้งหนี้ → ใบส่งของกลับไปรอออกใบแจ้งหนี้

            $table->index('id_invoice');
        });

        // ย้ายความสัมพันธ์เดิมมาฝั่งใบส่งของ
        DB::statement('
            UPDATE delivery_notes dn
            JOIN invoices i ON i.id_delivery_note = dn.id_delivery_note
            SET dn.id_invoice = i.id_invoice
            WHERE dn.id_invoice IS NULL
        ');

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('id_quotation')->nullable()->change();

            $table->unsignedSmallInteger('credit_days')->default(0)->after('invoice_date');
            $table->date('due_date')->nullable()->after('credit_days');
            $table->date('bill_received_date')->nullable()->after('due_date');

            $table->index('due_date');
        });

        // ใบแจ้งหนี้เดิม → ใช้เครดิตเทอมเริ่มต้นในหน้าตั้งค่า (ไม่มีค่า = 30 วัน)
        $credit = (int) (DB::table('settings')->where('key', 'doc.credit_days')->value('value') ?? 30);

        DB::table('invoices')->whereNull('due_date')->update([
            'credit_days' => $credit,
            'due_date'    => DB::raw("DATE_ADD(invoice_date, INTERVAL {$credit} DAY)"),
        ]);

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_details', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropColumn(['credit_days', 'due_date', 'bill_received_date']);
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['id_invoice']);
            $table->dropIndex(['id_invoice']);
            $table->dropColumn('id_invoice');
        });

        // หมายเหตุ: ไม่เปลี่ยน invoices.id_quotation กลับเป็น NOT NULL
        // เพราะถ้ามีใบแจ้งหนี้ที่ไม่มีใบเสนอราคาอยู่แล้ว จะ rollback ไม่ผ่าน
    }
};
