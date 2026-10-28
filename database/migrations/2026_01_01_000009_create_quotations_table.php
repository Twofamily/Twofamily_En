<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->bigIncrements('id_quot');
            // เลขที่เอกสาร QT-2569-0001 (unique ธรรมดาพอ เพราะไม่นำเลขกลับมาใช้ซ้ำ)
            $table->string('code_quot', 20)->nullable()->unique();
            $table->unsignedInteger('id_customer');
            $table->date('date_quot');
            $table->date('end_quot')->nullable();
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');

            // ผู้จัดทำเอกสาร (บันทึกตอนสร้าง) และผู้อนุมัติ (บันทึกตอนกดอนุมัติ)
            $table->unsignedBigInteger('issued_by_user_id')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable();

            // decimal เพื่อไม่ให้สตางค์หาย
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            // เก็บอัตรา VAT ณ วันที่ออกเอกสาร ถ้าอนาคตเปลี่ยนอัตรา เอกสารเก่ายังพิมพ์ถูก
            $table->decimal('vat_rate', 5, 2)->default(7.00);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->onDelete('cascade');

            // ลบ user ทิ้ง เอกสารเก่าต้องไม่หายตาม
            $table->foreign('issued_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
