<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * จดผู้ใช้ที่สร้างเอกสารไว้ใน created_by_user_id ให้อัตโนมัติ
 * ใช้แสดงชื่อ + ลายเซ็นผู้ออกเอกสารใน PDF
 */
trait RecordsCreator
{
    public static function bootRecordsCreator(): void
    {
        static::creating(function ($model) {
            $model->created_by_user_id ??= auth()->id();
        });
    }

    /** ผู้ออกเอกสาร */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
