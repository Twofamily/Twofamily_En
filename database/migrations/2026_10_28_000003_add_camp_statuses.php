<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * สถานะแคมป์ตามวงจรงานจริง
 *
 *   preparing  เตรียมงาน         เปิดแคมป์แล้ว ยังไม่ได้ส่งของ (ส่งใบส่งของใบแรก → เปลี่ยนเป็น active ให้เอง)
 *   active     กำลังดำเนินงาน
 *   completed  ส่งงานครบ รอเก็บเงิน   ส่งของไม่ได้แล้ว แต่ยังออกใบแจ้งหนี้ / รับเงินได้
 *   closed     ปิดแคมป์          ปิดได้เมื่อแจ้งหนี้ครบ เก็บเงินครบ และถอนรถออกหมด
 *
 * แคมป์เดิมที่เป็น active / closed ยังเป็นค่าเดิม
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->enum('status_camp', ['preparing', 'active', 'completed', 'closed'])
                  ->default('preparing')
                  ->change();
        });
    }

    public function down(): void
    {
        // สถานะใหม่ → ค่าที่ใกล้เคียงที่สุดของแบบเดิม
        DB::table('camps')->whereIn('status_camp', ['preparing', 'completed'])->update(['status_camp' => 'active']);

        Schema::table('camps', function (Blueprint $table) {
            $table->enum('status_camp', ['active', 'closed'])->default('active')->change();
        });
    }
};
