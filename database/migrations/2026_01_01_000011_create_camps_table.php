<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แคมป์ = หน้างานของลูกค้า 1 แห่ง
 * 1 แคมป์มีใบสั่งขายได้หลายใบ (ความสัมพันธ์อยู่ที่ sales_orders.id_camp)
 *
 * camps.id_so เป็นของเดิม (ยุค 1 ใบสั่งขาย = 1 แคมป์) โค้ดใหม่ไม่ใช้แล้ว
 * เก็บไว้ก่อนเพื่อให้โครงสร้างตรงกับของเดิม foreign key ของคอลัมน์นี้
 * ไปเพิ่มในไฟล์ create_sales_orders เพราะตอนนี้ยังไม่มีตาราง sales_orders
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camps', function (Blueprint $table) {
            $table->bigIncrements('id_camp');
            $table->unsignedInteger('id_customer');        // ต้องเป็น int ให้ตรงกับ customers
            $table->unsignedBigInteger('id_so')->nullable();
            $table->unsignedBigInteger('id_quot')->nullable();
            $table->string('code_camp', 20)->unique();     // CP-2569-0001
            $table->string('name_camp');
            $table->string('address_detail')->nullable();
            $table->string('subdistrict', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('zipcode', 5)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 10)->nullable();
            $table->enum('status_camp', ['active', 'closed'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->onDelete('cascade');

            $table->foreign('id_quot')
                  ->references('id_quot')->on('quotations')
                  ->nullOnDelete();

            $table->index(['id_customer', 'status_camp']);
            $table->index('id_quot');
            $table->unique(['id_so', 'deleted_at'], 'camps_so_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camps');
    }
};
