<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->bigIncrements('id_so');
            $table->string('code_so', 20)->unique();       // SO-2569-0001

            // อ้างอิงใบเสนอราคา (1 ใบเสนอราคา → 1 ใบสั่งขาย)
            // nullable เผื่อลูกค้าสั่งตรงโดยไม่ผ่านใบเสนอราคา
            $table->unsignedBigInteger('id_quot')->nullable();
            $table->unsignedInteger('id_customer');
            // แคมป์ที่ใบสั่งขายนี้อยู่ (ลูกค้าสั่งเพิ่มกลางงาน = เปิดใบสั่งขายใหม่ในแคมป์เดิม)
            $table->unsignedBigInteger('id_camp')->nullable();

            $table->date('order_date');                    // วันที่ลูกค้าสั่งซื้อ
            $table->date('due_date')->nullable();          // กำหนดส่งมอบ
            $table->string('po_number', 50)->nullable();   // เลขที่ PO ฝั่งลูกค้า

            $table->enum('status', [
                'draft',        // ร่าง แก้ไขได้
                'confirmed',    // ยืนยันแล้ว ล็อกรายการ พร้อมเปิดแคมป์
                'in_progress',  // เปิดแคมป์แล้ว กำลังส่งงาน
                'closed',       // ปิดงาน
                'cancelled',    // ยกเลิก
            ])->default('draft');

            // เก็บตัวเลขที่คำนวณแล้ว กันเอกสารย้อนหลังเปลี่ยนค่า
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_quot')
                  ->references('id_quot')->on('quotations')
                  ->onDelete('restrict');      // มี SO แล้วห้ามลบใบเสนอราคา

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->onDelete('restrict');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->restrictOnDelete();        // มีใบสั่งขายอยู่ในแคมป์ ห้ามลบแคมป์

            // 1 ใบเสนอราคา ออกใบสั่งขายได้ใบเดียว
            // พ่วง deleted_at เพราะ MySQL มอง NULL != NULL ลบ SO แล้วออกใหม่ได้
            $table->unique(['id_quot', 'deleted_at'], 'so_quot_deleted_unique');
            $table->index(['id_customer', 'status']);
            $table->index('order_date');
            $table->index('id_camp');
        });

        // foreign key ของ camps.id_so (คอลัมน์เดิม ดูหมายเหตุในไฟล์ create_camps)
        Schema::table('camps', function (Blueprint $table) {
            $table->foreign('id_so')
                  ->references('id_so')->on('sales_orders')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropForeign(['id_so']);
        });

        Schema::dropIfExists('sales_orders');
    }
};
