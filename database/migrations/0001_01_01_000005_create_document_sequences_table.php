<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 30);
            $table->string('period', 10);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            // 1 ประเภท + 1 ช่วงเวลา มีได้แถวเดียว
            // กันไม่ให้สร้างตัวนับซ้อนกันเวลามีคนบันทึกพร้อมกัน
            $table->unique(['doc_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
