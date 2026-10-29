<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * เอกสารที่ "ยกเลิก" ได้ แต่ไม่ถูกลบออกจากฐานข้อมูล
 *
 * ต่อยอดจาก SoftDeletes โดยใช้คอลัมน์ cancelled_at แทน deleted_at
 * (model ต้องประกาศ const DELETED_AT = 'cancelled_at')
 *
 *   - query ปกติ / relation ทุกตัว ไม่เห็นเอกสารที่ยกเลิกแล้ว → ยอดเงิน ยอดส่งของ ไม่นับให้อัตโนมัติ
 *   - หน้ารายการ / หน้าดู / PDF ใช้ withTrashed() เพื่อให้ยังค้นและเปิดดูได้
 *   - เลขที่เอกสารยังอยู่ ไม่มีเลขหาย
 */
trait Cancellable
{
    use SoftDeletes;

    /** ยกเลิกเอกสาร พร้อมบันทึกผู้ยกเลิกและเหตุผล */
    public function cancel(string $reason, ?int $userId = null): void
    {
        $this->forceFill([
            'cancel_reason'        => $reason,
            'cancelled_by_user_id' => $userId ?? auth()->id(),
        ])->save();

        $this->delete(); // SoftDeletes → ใส่ cancelled_at
    }

    public function isCancelled(): bool
    {
        return $this->trashed();
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /** ใช้ในหน้ารายการ: ทั้งหมด / เฉพาะที่ยกเลิก / เฉพาะที่ใช้งาน */
    public function scopeCancelledFilter($query, ?string $status)
    {
        return $status === 'cancelled'
            ? $query->onlyTrashed()
            : $query->withTrashed()->when($status !== null, fn ($q) => $q->whereNull($this->getQualifiedDeletedAtColumn()));
    }
}
