<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff', 'viewer'])
                  ->default('staff')
                  ->after('password')
                  ->comment('สิทธิ์การใช้งาน');

            $table->boolean('is_active')
                  ->default(true)
                  ->after('role')
                  ->comment('สถานะบัญชี: 1=ใช้งานได้ 0=ระงับ');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};