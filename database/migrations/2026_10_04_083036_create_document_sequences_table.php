<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวนับเลขที่เอกสาร
 *
 * 1 แถว = เลขล่าสุดของเอกสาร 1 ประเภท ใน 1 ช่วงเวลา
 * เช่น (sales_order, 2569, 3) → ใบถัดไปคือ SO-2569-0004
 *
 * period:
 *   '2569'    เริ่มนับใหม่ทุกปี
 *   '2569-10' เริ่มนับใหม่ทุกเดือน
 *   'all'     ไม่เริ่มนับใหม่
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 30);
            $table->string('period', 10);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            // 1 ประเภท + 1 ช่วงเวลา มีได้แถวเดียว
            // เป็นตัวกันไม่ให้สร้างตัวนับซ้อนกันเวลามีคนบันทึกพร้อมกัน
            $table->unique(['doc_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};