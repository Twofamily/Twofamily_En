<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ค่าน้ำมันแต่ละรอบเป็นของแคมป์ไหน
 *
 * ใช้คิดต้นทุนรายแคมป์บนแดชบอร์ด (กำไรรายแคมป์ = รายได้ของแคมป์ − ค่าน้ำมันของแคมป์)
 * nullable เพราะบางรอบไม่ได้วิ่งให้แคมป์ เช่น ส่งสินค้าตรงให้ลูกค้า
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_records', function (Blueprint $table) {
            // camps.id_camp เป็น bigIncrements → ต้องใช้ unsignedBigInteger
            $table->unsignedBigInteger('id_camp')->nullable()->after('trucks_id_truck');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->nullOnDelete();   // ลบแคมป์ทิ้ง บันทึกน้ำมันยังอยู่ แค่ไม่ผูกแคมป์

            $table->index(['id_camp', 'date_record']);
        });
    }

    public function down(): void
    {
        Schema::table('fuel_records', function (Blueprint $table) {
            $table->dropForeign(['id_camp']);
            $table->dropIndex(['id_camp', 'date_record']);
            $table->dropColumn('id_camp');
        });
    }
};
