<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'id_payment';

    protected $fillable = [
        'id_invoice',
        'paid_at',
        'amount',
        'method',
        'bank_name',
        'reference_no',
        'note',
        'slip_path',
        'slip_original_name',
        'slip_hash',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    // วิธีชำระที่อนุญาต => ป้ายภาษาไทย
    public const METHODS = [
        'transfer' => 'โอนเงิน',
        'cash'     => 'เงินสด',
        'cheque'   => 'เช็ค',
    ];

    // ชนิดไฟล์และขนาดสูงสุดของหลักฐาน (ใช้ร่วมกับ validation ใน controller)
    public const SLIP_MIMES  = 'jpg,jpeg,png,pdf';
    public const SLIP_MAX_KB = 5120; // 5 MB

    // โฟลเดอร์เก็บสลิปใน disk 'local' (private ไม่เปิดสาธารณะ)
    public const SLIP_DIR = 'payment-slips';

    /* ---------- Relationships ---------- */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /* ---------- Helpers ---------- */

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function isPdfSlip(): bool
    {
        return str_ends_with(strtolower($this->slip_path), '.pdf');
    }
}