<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * รายการชำระเงิน + หลักฐาน
 *
 * ผูกได้ 2 ทาง
 *   - id_invoice : ชำระตามใบแจ้งหนี้ (แบบเดิม)
 *   - id_receipt : หลักฐานของใบเสร็จรับเงินล่วงหน้า / ขายสด
 *
 * รายการชนิดพิเศษ method = 'advance'
 *   = หักเงินรับล่วงหน้าในใบแจ้งหนี้ ไม่มีสลิป (สลิปอยู่ที่ใบเสร็จล่วงหน้าแล้ว)
 *   id_advance_receipt บอกว่าหักจากใบเสร็จล่วงหน้าใบไหน
 */
class Payment extends Model
{
    protected $primaryKey = 'id_payment';

    protected $fillable = [
        'id_invoice',
        'id_receipt',
        'id_advance_receipt',
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

    // วิธีชำระที่ผู้ใช้เลือกได้ในฟอร์ม => ป้ายภาษาไทย
    public const METHODS = [
        'transfer' => 'โอนเงิน',
        'cash'     => 'เงินสด',
        'cheque'   => 'เช็ค',
    ];

    // ระบบสร้างเองตอนกด "หักเงินรับล่วงหน้า" — ไม่อยู่ใน dropdown
    public const METHOD_ADVANCE = 'advance';

    // ชนิดไฟล์และขนาดสูงสุดของหลักฐาน (ใช้ร่วมกับ validation ใน controller)
    public const SLIP_MIMES  = 'jpg,jpeg,png,pdf';
    public const SLIP_MAX_KB = 5120; // 5 MB

    // โฟลเดอร์เก็บสลิปใน disk 'local' (private ไม่เปิดสาธารณะ)
    public const SLIP_DIR = 'payment-slips';

    /**
     * วิธีชำระที่ไม่บังคับแนบไฟล์
     * เงินสดไม่มีสลิปจากธนาคาร หลักฐานคือ "ผู้รับเงิน" ที่ระบบบันทึก (recorded_by_user_id)
     * + ลายเซ็นผู้จ่าย/ผู้รับเงินบนใบเสร็จ — แนบรูปเพิ่มได้ถ้ามี
     */
    public const METHODS_WITHOUT_SLIP = ['cash'];

    /**
     * กฎ validation ของไฟล์หลักฐาน ใช้ร่วมกันทั้งหน้าใบแจ้งหนี้และหน้าออกใบเสร็จ
     *   - ปิด "ต้องแนบหลักฐาน" ในหน้าตั้งค่า → ไม่บังคับทุกวิธี
     *   - เปิดอยู่ → บังคับ ยกเว้นเงินสด
     */
    public static function slipRules(): array
    {
        $rules = ['nullable', 'file', 'mimes:' . self::SLIP_MIMES, 'max:' . self::SLIP_MAX_KB];

        if (Setting::get('payment.require_slip', true)) {
            array_unshift($rules, 'required_unless:method,' . implode(',', self::METHODS_WITHOUT_SLIP));
        }

        return $rules;
    }

    /** วิธีชำระนี้ต้องแนบไฟล์หรือไม่ (ใช้ฝั่งหน้าจอ) */
    public static function slipRequiredFor(?string $method): bool
    {
        return Setting::get('payment.require_slip', true)
            && ! in_array($method, self::METHODS_WITHOUT_SLIP, true);
    }

    /* ---------- Relationships ---------- */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    /** ใบเสร็จ (advance / direct) ที่รายการนี้เป็นหลักฐาน */
    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'id_receipt', 'id_receipt');
    }

    /** ใบเสร็จรับเงินล่วงหน้าที่ถูกหักมาใช้ในรายการนี้ */
    public function advanceReceipt()
    {
        return $this->belongsTo(Receipt::class, 'id_advance_receipt', 'id_receipt');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /* ---------- Helpers ---------- */

    public function getMethodLabelAttribute(): string
    {
        if ($this->method === self::METHOD_ADVANCE) {
            return 'หักเงินรับล่วงหน้า';
        }

        return self::METHODS[$this->method] ?? $this->method;
    }

    public function isAdvanceApplication(): bool
    {
        return $this->method === self::METHOD_ADVANCE;
    }

    public function hasSlip(): bool
    {
        return ! empty($this->slip_path);
    }

    public function isPdfSlip(): bool
    {
        return $this->hasSlip() && str_ends_with(strtolower($this->slip_path), '.pdf');
    }

    /** เลขเอกสารที่หลักฐานนี้ผูกอยู่ ไว้แสดงในข้อความแจ้งเตือน */
    public function ownerCode(): string
    {
        return $this->invoice->code_inv
            ?? $this->receipt->code_rc
            ?? '-';
    }
}
