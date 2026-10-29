<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ใบเสร็จมี 3 แบบ (receipt_type)
 *   invoice  ออกจากใบแจ้งหนี้
 *   advance  รับเงินล่วงหน้า (ไม่มีใบแจ้งหนี้)
 *   direct   ขายสด (ไม่มีใบแจ้งหนี้)
 * แบบ advance / direct เก็บรายการไว้ที่ receipt_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('id_receipt');
            $table->string('code_rc', 20)->nullable()->unique();   // RC-2569-0001
            $table->string('receipt_type', 20)->default('invoice');
            $table->unsignedBigInteger('id_invoice')->nullable();
            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('id_camp')->nullable();

            // เก็บยอดเป็นคอลัมน์จริง ไม่คำนวณใหม่ตอนพิมพ์
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('advance_deducted', 12, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->dateTime('date_receipt');
            $table->string('description')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('id_camp')->references('id_camp')->on('camps')->nullOnDelete();

            $table->index('receipt_type');
            $table->index('id_camp');
            $table->index('id_invoice');
        });

        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id('id_receipt_item');
            $table->unsignedBigInteger('id_receipt');
            $table->unsignedBigInteger('id_product')->nullable();  // advance ไม่มีสินค้า
            $table->string('description')->nullable();             // ข้อความรายการ (advance)
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_receipt')->references('id_receipt')->on('receipts')->cascadeOnDelete();
            $table->foreign('id_product')->references('id_product')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipts');
    }
};
