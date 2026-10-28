<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ใบแจ้งหนี้รู้ว่าตัวเองเป็นของแคมป์ไหน
 *
 * ใช้ 2 อย่าง
 *   1. หักเงินรับล่วงหน้าของแคมป์นั้นได้
 *   2. สรุปยอดแจ้งหนี้ / ค้างชำระรายแคมป์ โดยไม่ต้องไล่ผ่านใบส่งของ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('id_camp')->nullable()->after('id_customer');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->nullOnDelete();

            $table->index('id_camp');
        });

        // ใบแจ้งหนี้เดิม → เอาแคมป์จากใบส่งของที่อ้างอิง
        DB::statement('
            UPDATE invoices i
            JOIN delivery_notes dn ON dn.id_delivery_note = i.id_delivery_note
            SET i.id_camp = dn.id_camp
            WHERE i.id_camp IS NULL AND dn.id_camp IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['id_camp']);
            $table->dropIndex(['id_camp']);
            $table->dropColumn('id_camp');
        });
    }
};
