<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->increments('id_driver');

            $table->string('fname_driver', 255);
            $table->string('lname_driver', 255);

            // ที่อยู่ (แยก บ้านเลขที่ / หมู่ / ซอย-ถนน)
            $table->string('address_no', 50)->nullable();       // บ้านเลขที่
            $table->string('moo', 20)->nullable();              // หมู่ที่
            $table->string('address_detail', 255)->nullable();  // ซอย / ถนน / อาคาร
            $table->string('subdistrict', 100)->nullable();     // ตำบล
            $table->string('district', 100)->nullable();        // อำเภอ
            $table->string('province', 100)->nullable();        // จังหวัด
            $table->string('zipcode', 5)->nullable();           // รหัสไปรษณีย์

            $table->string('phone_driver', 10)->nullable();
            $table->string('citizenid_driver', 13)->nullable();
            $table->string('citizen_image', 255)->nullable();   // รูปใบขับขี่/บัตรประชาชน

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};