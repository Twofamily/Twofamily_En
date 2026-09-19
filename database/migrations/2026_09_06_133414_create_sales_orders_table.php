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

            $table->string('code_so', 20)->unique();     // SO-2569-0001

            // อ้างอิงใบเสนอราคา (1 ใบเสนอราคา → 1 ใบสั่งขาย)
            // nullable เผื่อกรณีลูกค้าสั่งตรงโดยไม่ผ่านใบเสนอราคา
            $table->unsignedBigInteger('id_quot')->nullable();

            $table->unsignedInteger('id_customer');

            $table->date('order_date');                  // วันที่ลูกค้าสั่งซื้อ
            $table->date('due_date')->nullable();        // กำหนดส่งมอบ
            $table->string('po_number', 50)->nullable(); // เลขที่ PO ฝั่งลูกค้า

            $table->enum('status', [
                'draft',        // ร่าง แก้ไขได้
                'confirmed',    // ยืนยันแล้ว ล็อกรายการ พร้อมเปิดแคมป์
                'in_progress',  // เปิดแคมป์แล้ว กำลังส่งงาน
                'closed',       // ปิดงาน
                'cancelled',    // ยกเลิก
            ])->default('draft');

            // เก็บตัวเลขที่คำนวณแล้วเป็นคอลัมน์ กันเอกสารย้อนหลังเปลี่ยนค่า
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_quot')
                  ->references('id_quot')->on('quotations')
                  ->onDelete('restrict');   // มี SO แล้วห้ามลบใบเสนอราคา

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->onDelete('restrict');

            // 1 ใบเสนอราคา ออกใบสั่งขายได้ใบเดียว
            // ต้องพ่วง deleted_at เพราะ MySQL มอง NULL != NULL
            // ถ้าใส่ unique เฉย ๆ พอลบ SO ทิ้ง ใบเสนอราคานั้นจะออก SO ใหม่ไม่ได้ตลอดกาล
            $table->unique(['id_quot', 'deleted_at'], 'so_quot_deleted_unique');

            $table->index(['id_customer', 'status']);
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};