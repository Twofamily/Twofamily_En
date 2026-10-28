<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('id_payment');

            // ชำระของใบแจ้งหนี้ใบไหน (ว่างได้ ถ้าเป็นของใบเสร็จ advance / direct)
            $table->unsignedBigInteger('id_invoice')->nullable();
            // หลักฐานของใบเสร็จ advance / direct
            $table->unsignedBigInteger('id_receipt')->nullable();
            // รายการ "หักเงินรับล่วงหน้า" ชี้ไปที่ใบเสร็จรับเงินล่วงหน้าใบไหน
            $table->unsignedBigInteger('id_advance_receipt')->nullable();

            // ข้อมูลการชำระ
            $table->date('paid_at');
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);                     // transfer / cash / cheque
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_no', 50)->nullable();   // เลขอ้างอิงบนสลิป / เลขเช็ค
            $table->text('note')->nullable();

            // ไฟล์หลักฐาน (รายการหักเงินล่วงหน้าไม่มีสลิป เพราะสลิปอยู่ที่ใบเสร็จล่วงหน้าแล้ว)
            $table->string('slip_path')->nullable();
            $table->string('slip_original_name')->nullable();
            $table->char('slip_hash', 64)->nullable()->index();  // SHA-256 กันอัปสลิปซ้ำ

            // ผู้บันทึก
            $table->foreignId('recorded_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->foreign('id_invoice')
                  ->references('id_invoice')->on('invoices')
                  ->restrictOnDelete();      // มีการชำระแล้ว ห้ามลบใบแจ้งหนี้

            $table->foreign('id_receipt')
                  ->references('id_receipt')->on('receipts')
                  ->restrictOnDelete();

            $table->foreign('id_advance_receipt')
                  ->references('id_receipt')->on('receipts')
                  ->restrictOnDelete();      // ถูกหักไปแล้ว ห้ามลบใบเสร็จล่วงหน้า

            $table->index(['id_invoice', 'paid_at']);
            $table->index('id_receipt');
            $table->index('id_advance_receipt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
