<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * camps.id_quot ถูกสร้างไว้แล้วใน 2026_08_25_224648_add_quotation_to_camps_table
 * (พร้อม foreign key ไป quotations) ไฟล์นี้จึงเพิ่มให้เฉพาะเครื่องที่ยังไม่มีคอลัมน์
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('camps', 'id_quot')) {
            return;
        }

        Schema::table('camps', function (Blueprint $table) {
            $table->unsignedBigInteger('id_quot')
                ->nullable()
                ->after('id_customer');
        });
    }

    public function down(): void
    {
        // ไม่ลบคอลัมน์ เพราะ migration 2026_08_25_224648 เป็นเจ้าของคอลัมน์นี้
    }
};
