<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผูกคนขับเข้าแคมป์ ผ่านการมอบหมายรถ
 *
 * 1 แถวใน camp_truck = รถ 1 คัน + คนขับ 1 คน ทำงานที่แคมป์ 1 แห่ง ในช่วงวันที่หนึ่ง
 * ใช้ตารางเดิม เพราะในงานจริงคนขับมากับรถ จะได้ไม่มีข้อมูลสองที่ที่ต้องตามให้ตรงกัน
 *
 * id_driver เป็น nullable: รถที่มอบหมายไว้ก่อนหน้านี้ยังไม่มีคนขับ ใส่ทีหลังได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camp_truck', function (Blueprint $table) {
            // drivers.id_driver เป็น increments → ต้องใช้ unsignedInteger
            $table->unsignedInteger('id_driver')->nullable()->after('id_truck');

            $table->foreign('id_driver')
                  ->references('id_driver')->on('drivers')
                  ->nullOnDelete();

            $table->index(['id_driver', 'released_date']);
        });
    }

    public function down(): void
    {
        Schema::table('camp_truck', function (Blueprint $table) {
            $table->dropForeign(['id_driver']);
            $table->dropIndex(['id_driver', 'released_date']);
            $table->dropColumn('id_driver');
        });
    }
};
