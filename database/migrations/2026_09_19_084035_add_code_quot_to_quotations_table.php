<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เปลี่ยน code_quot จาก accessor (ประกอบจาก id ตอนแสดงผล) ให้เป็นคอลัมน์จริง
 * แล้วเติมเลขให้ใบเสนอราคาเดิมทุกใบ รวมใบที่ถูก soft delete
 * รูปแบบเดียวกับเอกสารอื่น: QT-2569-0001
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            // unique ธรรมดาพอ เพราะเลขที่ไม่ถูกนำกลับมาใช้ซ้ำแม้เอกสารถูก soft delete
            $t->string('code_quot', 20)->nullable()->unique()->after('id_quot');
        });

        // เรียงตามวันที่ออกใบเสนอราคา แล้วตาม id / เลขรันเริ่มใหม่ทุกปี พ.ศ.
        $counters = [];

        DB::table('quotations')
            ->orderBy('date_quot')
            ->orderBy('id_quot')
            ->get(['id_quot', 'date_quot', 'created_at'])
            ->each(function ($row) use (&$counters) {
                $date = $row->date_quot ?? $row->created_at ?? now();
                $year = Carbon::parse($date)->year + 543;

                $counters[$year] = ($counters[$year] ?? 0) + 1;

                DB::table('quotations')->where('id_quot', $row->id_quot)->update([
                    'code_quot' => sprintf('QT-%d-%04d', $year, $counters[$year]),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            $t->dropUnique(['code_quot']);
            $t->dropColumn('code_quot');
        });
    }
};