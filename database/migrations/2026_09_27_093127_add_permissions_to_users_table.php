<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สิทธิ์รายหน้าของผู้ใช้แต่ละคน เก็บเป็น JSON เช่น
     *   {"quotations":"edit","sales-orders":"view","customers":"none"}
     *
     * ค่า NULL หรือไม่มีคีย์ = ใช้สิทธิ์ตามบทบาท (role) แบบเดิม
     * ผู้ใช้ที่มีอยู่แล้วจึงใช้งานได้เหมือนเดิมทุกอย่าง
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};