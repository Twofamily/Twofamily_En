<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แยกชื่อผู้ใช้เป็น ชื่อ / นามสกุล
 *
 * คอลัมน์ name ยังอยู่ (Jetstream, PDF, หน้าอื่น ๆ ใช้อยู่) และ Model จะประกอบให้เองทุกครั้งที่บันทึก
 *   name = first_name + ' ' + last_name
 * ข้อมูลเดิม: ตัดคำแรกเป็นชื่อ ที่เหลือเป็นนามสกุล เช่น "สมหญิง ขยันงาน" → สมหญิง / ขยันงาน
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        DB::table('users')->orderBy('id')->each(function ($user) {
            $parts = preg_split('/\s+/u', trim((string) $user->name), 2);

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
