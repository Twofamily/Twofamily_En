<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์เลขที่เอกสารให้ ใบส่งของ / ใบแจ้งหนี้ / ใบเสร็จ
 * แล้วเติมเลขให้ข้อมูลเก่าตามลำดับวันที่ รูปแบบเดียวกับใบสั่งขาย (SO-2569-0003)
 */
return new class extends Migration
{
    /** [ตาราง, primary key, คอลัมน์เลขที่, prefix, คอลัมน์วันที่ของเอกสาร] */
    private array $docs = [
        ['delivery_notes', 'id_delivery_note', 'code_dn',  'DN',  'delivery_date'],
        ['invoices',       'id_invoice',       'code_inv', 'INV', 'created_at'],
        ['receipts',       'id_receipt',       'code_rc',  'RC',  'date_receipt'],
    ];

    public function up(): void
    {
        foreach ($this->docs as [$table, $pk, $column]) {
            Schema::table($table, function (Blueprint $t) use ($pk, $column) {
                // unique ธรรมดาพอ เพราะเลขที่ไม่ถูกนำกลับมาใช้ซ้ำแม้เอกสารถูก soft delete
                $t->string($column, 20)->nullable()->unique()->after($pk);
            });
        }

        // เติมเลขให้ข้อมูลเก่า เรียงตามวันที่เอกสาร แล้วตาม id
        foreach ($this->docs as [$table, $pk, $column, $prefix, $dateCol]) {
            $counters = [];

            DB::table($table)
                ->orderBy($dateCol)
                ->orderBy($pk)
                ->get([$pk, $dateCol, 'created_at'])
                ->each(function ($row) use ($table, $pk, $column, $prefix, $dateCol, &$counters) {
                    $date = $row->{$dateCol} ?? $row->created_at ?? now();
                    $year = \Illuminate\Support\Carbon::parse($date)->year + 543;

                    $counters[$year] = ($counters[$year] ?? 0) + 1;

                    DB::table($table)->where($pk, $row->{$pk})->update([
                        $column => sprintf('%s-%d-%04d', $prefix, $year, $counters[$year]),
                    ]);
                });
        }
    }

    public function down(): void
    {
        foreach ($this->docs as [$table, , $column]) {
            Schema::table($table, function (Blueprint $t) use ($table, $column) {
                $t->dropUnique([$column]);
                $t->dropColumn($column);
            });
        }
    }
};
