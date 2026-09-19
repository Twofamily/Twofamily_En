<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * สร้างเลขที่เอกสารรูปแบบเดียวกันทุกชนิด: PREFIX-ปีพ.ศ.-เลขรัน 4 หลัก
 * เช่น DN-2569-0001, INV-2569-0012 และเลขรันจะเริ่ม 0001 ใหม่ทุกปี
 *
 * วิธีใช้ใน model:
 *     use HasDocumentCode;
 *     protected static string $codePrefix = 'DN';
 *     protected static string $codeColumn = 'code_dn';
 *
 * เลขที่จะถูกใส่ให้อัตโนมัติตอน create ไม่ต้องแก้ controller
 */
trait HasDocumentCode
{
    public static function bootHasDocumentCode(): void
    {
        static::creating(function ($model) {
            $column = static::$codeColumn;

            if (empty($model->{$column})) {
                $model->{$column} = static::nextDocumentCode();
            }
        });
    }

    public static function nextDocumentCode(?\DateTimeInterface $date = null): string
    {
        $date   = $date ? Carbon::instance($date) : now();
        $prefix = static::$codePrefix . '-' . ($date->year + 543) . '-';
        $column = static::$codeColumn;

        // withoutGlobalScopes: นับรวมเอกสารที่ soft delete ไปแล้วด้วย เลขที่จะได้ไม่ถูกใช้ซ้ำ
        $last = static::query()
            ->withoutGlobalScopes()
            ->where($column, 'like', $prefix . '%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
