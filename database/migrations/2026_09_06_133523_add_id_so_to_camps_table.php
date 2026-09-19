<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {

            // nullable เพื่อรองรับแคมป์เก่าที่ยังไม่มีใบสั่งขาย
            $table->unsignedBigInteger('id_so')->nullable()->after('id_customer');

            $table->foreign('id_so')
                  ->references('id_so')->on('sales_orders')
                  ->onDelete('restrict');   // มีแคมป์แล้วห้ามลบ SO

            // 1 ใบสั่งขาย เปิดได้แคมป์เดียว
            $table->unique(['id_so', 'deleted_at'], 'camps_so_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropUnique('camps_so_deleted_unique');
            $table->dropForeign(['id_so']);
            $table->dropColumn('id_so');
        });
    }
};