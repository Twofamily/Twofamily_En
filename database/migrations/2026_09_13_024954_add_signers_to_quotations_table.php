<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            // ผู้จัดทำเอกสาร บันทึกตอนสร้าง
            $table->unsignedBigInteger('issued_by_user_id')->nullable()->after('status');
            $table->timestamp('issued_at')->nullable()->after('issued_by_user_id');

            // ผู้อนุมัติ บันทึกตอนกดอนุมัติ
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('issued_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');

            // nullOnDelete: ถ้าลบ user ทิ้ง เอกสารเก่าต้องไม่หายตาม
            $table->foreign('issued_by_user_id')
                  ->references('id')->on('users')->nullOnDelete();

            $table->foreign('approved_by_user_id')
                  ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['issued_by_user_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn([
                'issued_by_user_id',
                'issued_at',
                'approved_by_user_id',
                'approved_at',
            ]);
        });
    }
};