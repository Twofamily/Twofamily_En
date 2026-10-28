<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตาราง address ไม่มีโค้ดไหนใช้แล้ว — ที่อยู่ลูกค้าเก็บในตาราง customers
 * (address_detail / subdistrict / district / province / zipcode)
 *
 * ลบตารางทิ้ง ถ้าย้อนกลับ (rollback) จะสร้างตารางเปล่ากลับมาให้เหมือนเดิม
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('address');
    }

    public function down(): void
    {
        if (Schema::hasTable('address')) {
            return;
        }

        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_customer');
            $table->text('customer_address');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->cascadeOnDelete();
        });
    }
};
