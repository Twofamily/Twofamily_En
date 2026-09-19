<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // เก็บ path สัมพัทธ์จาก public/ เช่น images/signatures/user_3.png
            $table->string('signature_path')->nullable()->after('email');

            // ตำแหน่งที่จะพิมพ์ใต้ชื่อในเอกสาร เช่น "เจ้าหน้าที่ฝ่ายขาย"
            $table->string('position')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'position']);
        });
    }
};