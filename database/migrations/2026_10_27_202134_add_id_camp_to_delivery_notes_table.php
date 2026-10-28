<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * delivery_notes.id_camp ถูกสร้างไว้แล้วใน 2026_08_26_000000_add_camp_to_delivery_notes_table
 * (พร้อม foreign key ไป camps) ไฟล์นี้จึงเพิ่มให้เฉพาะเครื่องที่ยังไม่มีคอลัมน์
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('delivery_notes', 'id_camp')) {
            return;
        }

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_camp')
                ->nullable()
                ->index();
        });
    }

    public function down(): void
    {
        // ไม่ลบคอลัมน์ เพราะ migration 2026_08_26_000000 เป็นเจ้าของคอลัมน์นี้
    }
};
