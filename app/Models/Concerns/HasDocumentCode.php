<?php

namespace App\Models\Concerns;

use App\Models\Setting;
use App\Services\DocumentNumberService;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * ใส่เลขที่เอกสารให้อัตโนมัติตอน create
 *
 * รูปแบบเลข (prefix / ปี พ.ศ.-ค.ศ. / รอบการนับใหม่ / จำนวนหลัก)
 * มาจากหน้าตั้งค่าระบบ ผ่าน DocumentNumberService
 *
 * วิธีใช้ใน model:
 *     use HasDocumentCode;
 *
 * และลงทะเบียน model กับคอลัมน์ไว้ใน config/settings.php → document_types
 * ไม่ต้องประกาศ prefix ใน model อีกแล้ว
 */
trait HasDocumentCode
{
    public static function bootHasDocumentCode(): void
    {
        static::creating(function ($model) {
            $column = static::documentCodeColumn();

            // ถ้าส่งเลขมาเอง (เช่นนำเข้าข้อมูลเก่า) จะไม่ทับ
            if (empty($model->{$column})) {
                $model->{$column} = app(DocumentNumberService::class)
                    ->next(static::documentType());
            }
        });
    }

    /** ประเภทเอกสารของ model นี้ เช่น 'invoice' */
    public static function documentType(): string
    {
        foreach (Setting::documentTypes() as $type => $info) {
            if (($info['model'] ?? null) === static::class) {
                return $type;
            }
        }

        throw new LogicException(
            static::class . ' ยังไม่ได้ลงทะเบียนใน config/settings.php → document_types'
        );
    }

    /** คอลัมน์ที่เก็บเลขที่เอกสาร เช่น 'code_inv' */
    public static function documentCodeColumn(): string
    {
        return Setting::documentTypes()[static::documentType()]['column'];
    }

    /**
     * ดูเลขถัดไปโดยไม่นับเพิ่ม
     * ใช้ชื่อเมธอดเดิม เพื่อให้โค้ดที่เคยเรียกใช้อยู่ทำงานต่อได้
     */
    public static function nextDocumentCode(?DateTimeInterface $date = null): string
    {
        return app(DocumentNumberService::class)->peek(
            static::documentType(),
            $date ? Carbon::instance($date) : null
        );
    }
}