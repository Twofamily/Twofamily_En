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

            // ผูกกับใบแจ้งหนี้ (invoices.id_invoice = bigint unsigned)
            $table->unsignedBigInteger('id_invoice');
            $table->foreign('id_invoice')
                  ->references('id_invoice')->on('invoices')
                  ->restrictOnDelete(); // มีการชำระแล้ว ห้ามลบใบแจ้งหนี้

            // ข้อมูลการชำระ
            $table->date('paid_at');
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);                 // transfer / cash / cheque
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_no', 50)->nullable(); // เลขอ้างอิงบนสลิป / เลขเช็ค
            $table->text('note')->nullable();

            // ไฟล์หลักฐาน
            $table->string('slip_path');
            $table->string('slip_original_name')->nullable();
            $table->char('slip_hash', 64)->index();       // SHA-256 ใช้กันอัปสลิปซ้ำ

            // ผู้บันทึก
            $table->foreignId('recorded_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['id_invoice', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};