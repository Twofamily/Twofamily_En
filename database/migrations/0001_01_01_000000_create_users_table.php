<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            // เก็บ path สัมพัทธ์จาก public/ เช่น images/signatures/user_3.png
            $table->string('signature_path')->nullable();
            // ตำแหน่งที่จะพิมพ์ใต้ชื่อในเอกสาร เช่น "เจ้าหน้าที่ฝ่ายขาย"
            $table->string('position')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'staff', 'viewer'])
                  ->default('staff')
                  ->comment('สิทธิ์การใช้งาน');
            // สิทธิ์รายหน้า เช่น {"quotations":"edit","customers":"none"}
            // NULL หรือไม่มีคีย์ = ใช้สิทธิ์ตาม role
            $table->json('permissions')->nullable();
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('สถานะบัญชี: 1=ใช้งานได้ 0=ระงับ');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
