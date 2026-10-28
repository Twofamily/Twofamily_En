<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ใบเสร็จออกได้ 3 แบบ
 *
 *   invoice : รับชำระตามใบแจ้งหนี้ (แบบเดิม)
 *   advance : รับเงินล่วงหน้า ผูกกับแคมป์ ยังไม่ต้องมีใบส่งของ/ใบแจ้งหนี้
 *   direct  : ขายสด ลูกค้าจ่ายทันที ไม่ต้องมีแคมป์
 *
 * ทุกแบบยังต้องมีหลักฐานการชำระ (ตาราง payments) เหมือนเดิม
 *   - invoice : หลักฐานผูกกับใบแจ้งหนี้        (payments.id_invoice)
 *   - advance / direct : หลักฐานผูกกับใบเสร็จ  (payments.id_receipt)
 *
 * เมื่อเอาเงินล่วงหน้ามาหักในใบแจ้งหนี้ภายหลัง
 * จะสร้างรายการชำระ method = 'advance' ในใบแจ้งหนี้ และชี้กลับไปที่ใบเสร็จรับเงินล่วงหน้า
 * (payments.id_advance_receipt) จึงตามได้ว่าเงินล่วงหน้าแต่ละใบถูกใช้ไปเท่าไร
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- receipts ---------- */
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_type', 20)->default('invoice')->after('code_rc');

            // ใบเสร็จแบบ advance / direct ไม่มีใบแจ้งหนี้
            $table->unsignedBigInteger('id_invoice')->nullable()->change();

            $table->unsignedBigInteger('id_camp')->nullable()->after('id_customer');

            // เก็บยอดเป็นคอลัมน์จริง ไม่คำนวณใหม่ตอนพิมพ์
            $table->decimal('subtotal', 12, 2)->default(0)->after('id_camp');
            $table->decimal('vat_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('advance_deducted', 12, 2)->default(0)->after('vat_amount');

            $table->string('description')->nullable()->after('date_receipt');
            $table->text('note')->nullable()->after('description');

            $table->foreignId('issued_by_user_id')->nullable()->after('note')
                  ->constrained('users')->nullOnDelete();

            $table->foreign('id_camp')->references('id_camp')->on('camps')->nullOnDelete();

            $table->index('receipt_type');
            $table->index('id_camp');
            $table->index('id_invoice');
        });

        // ใบเสร็จเดิมทั้งหมดเป็นแบบ invoice → เติมยอดก่อน VAT / VAT จากรายการในใบแจ้งหนี้
        DB::statement('
            UPDATE receipts r
            JOIN (
                SELECT id_invoice, SUM(total) AS items_total
                FROM invoice_details
                GROUP BY id_invoice
            ) d ON d.id_invoice = r.id_invoice
            SET r.subtotal   = d.items_total,
                r.vat_amount = ROUND(d.items_total * 0.07, 2)
        ');

        DB::statement('
            UPDATE receipts r
            JOIN invoices i ON i.id_invoice = r.id_invoice
            SET r.id_camp = i.id_camp
            WHERE r.id_camp IS NULL
        ');

        /* ---------- receipt_items : รายการของใบเสร็จแบบ advance / direct ---------- */
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id('id_receipt_item');

            $table->unsignedBigInteger('id_receipt');
            $table->unsignedBigInteger('id_product')->nullable();   // advance ไม่มีสินค้า

            $table->string('description')->nullable();               // ข้อความรายการ (advance)
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamps();

            $table->foreign('id_receipt')->references('id_receipt')->on('receipts')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products')->nullOnDelete();
        });

        /* ---------- payments ---------- */
        Schema::table('payments', function (Blueprint $table) {
            // ต้องถอด FK ก่อนเปลี่ยนเป็น nullable แล้วค่อยใส่กลับ
            $table->dropForeign(['id_invoice']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('id_invoice')->nullable()->change();

            $table->foreign('id_invoice')
                  ->references('id_invoice')->on('invoices')
                  ->restrictOnDelete();

            // หลักฐานของใบเสร็จ advance / direct
            $table->unsignedBigInteger('id_receipt')->nullable()->after('id_invoice');
            $table->foreign('id_receipt')
                  ->references('id_receipt')->on('receipts')
                  ->restrictOnDelete();

            // รายการ "หักเงินรับล่วงหน้า" ในใบแจ้งหนี้ ชี้ไปที่ใบเสร็จรับเงินล่วงหน้าใบไหน
            $table->unsignedBigInteger('id_advance_receipt')->nullable()->after('id_receipt');
            $table->foreign('id_advance_receipt')
                  ->references('id_receipt')->on('receipts')
                  ->restrictOnDelete();   // ถูกหักไปแล้ว ห้ามลบใบเสร็จล่วงหน้า

            // รายการหักเงินล่วงหน้าไม่มีไฟล์สลิป (สลิปอยู่ที่ใบเสร็จล่วงหน้าแล้ว)
            $table->string('slip_path')->nullable()->change();
            $table->char('slip_hash', 64)->nullable()->change();

            $table->index('id_receipt');
            $table->index('id_advance_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['id_receipt']);
            $table->dropForeign(['id_advance_receipt']);
            $table->dropIndex(['id_receipt']);
            $table->dropIndex(['id_advance_receipt']);
            $table->dropColumn(['id_receipt', 'id_advance_receipt']);
        });

        Schema::dropIfExists('receipt_items');

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['id_camp']);
            $table->dropForeign(['issued_by_user_id']);
            $table->dropIndex(['receipt_type']);
            $table->dropIndex(['id_camp']);
            $table->dropIndex(['id_invoice']);
            $table->dropColumn([
                'receipt_type', 'id_camp', 'subtotal', 'vat_amount', 'advance_deducted',
                'description', 'note', 'issued_by_user_id',
            ]);
        });

        // หมายเหตุ: ไม่เปลี่ยน payments.id_invoice / receipts.id_invoice กลับเป็น NOT NULL
        // เพราะถ้ามีใบเสร็จแบบ advance / direct อยู่แล้ว จะ rollback ไม่ผ่าน
    }
};
