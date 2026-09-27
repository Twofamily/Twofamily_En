<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasDocumentCode;

    // เลขที่เอกสารรูปแบบ INV-2569-0001 สร้างให้อัตโนมัติตอน create
    protected static string $codePrefix = 'INV';
    protected static string $codeColumn = 'code_inv';

    protected $primaryKey = 'id_invoice';

    protected $fillable = [
        'code_inv',
        'id_quotation',
        'id_delivery_note',
        'id_customer',
        'discount',
        'total',
        'status'
    ];

    // สถานะการชำระ => ป้ายภาษาไทย + สี badge
    public const STATUSES = [
        'unpaid'  => ['label' => 'ยังไม่ชำระ',   'badge' => 'secondary'],
        'partial' => ['label' => 'ชำระบางส่วน', 'badge' => 'warning'],
        'paid'    => ['label' => 'ชำระครบแล้ว', 'badge' => 'success'],
    ];

    /* ---------- Relationships ---------- */

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'id_invoice');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quotation', 'id_quot');
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'id_delivery_note', 'id_delivery_note');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'id_invoice', 'id_invoice')
                    ->orderBy('paid_at')
                    ->orderBy('id_payment');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'id_invoice', 'id_invoice');
    }

    /* ---------- Status label ---------- */

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUSES[$this->status]['badge'] ?? 'secondary';
    }

    /* ---------- Payment logic ---------- */
    // คำนวณเป็น "สตางค์" (จำนวนเต็ม) เพื่อเลี่ยงปัญหาทศนิยมของ float

    protected function toSatang($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    public function paidAmount(): float
    {
        $sum = $this->relationLoaded('payments')
            ? $this->payments->sum(fn ($p) => $this->toSatang($p->amount))
            : $this->toSatang($this->payments()->sum('amount'));

        return $sum / 100;
    }

    public function remainingAmount(): float
    {
        $remain = $this->toSatang($this->total) - $this->toSatang($this->paidAmount());
        return max($remain, 0) / 100;
    }

    public function isFullyPaid(): bool
    {
        return $this->toSatang($this->paidAmount()) >= $this->toSatang($this->total);
    }

    public function hasPayments(): bool
    {
        return $this->relationLoaded('payments')
            ? $this->payments->isNotEmpty()
            : $this->payments()->exists();
    }

    public function hasReceipt(): bool
    {
        return $this->relationLoaded('receipt')
            ? $this->receipt !== null
            : $this->receipt()->exists();
    }

    // เงื่อนไขหลัก: มีหลักฐานอย่างน้อย 1 รายการ + ยอดครบ + ยังไม่เคยออกใบเสร็จ
    public function canIssueReceipt(): bool
    {
        return $this->hasPayments()
            && $this->isFullyPaid()
            && ! $this->hasReceipt();
    }

    // เพิ่ม/ลบรายการชำระได้เฉพาะตอนที่ยังไม่ออกใบเสร็จ
    public function canEditPayments(): bool
    {
        return ! $this->hasReceipt();
    }

    // อัปเดต status ตามยอดที่ชำระจริง — เรียกทุกครั้งหลังเพิ่ม/ลบรายการชำระ
    public function syncPaymentStatus(): void
    {
        $this->unsetRelation('payments'); // บังคับให้ query ยอดใหม่จาก DB

        if ($this->hasPayments() && $this->isFullyPaid()) {
            $status = 'paid';
        } elseif ($this->paidAmount() > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        if ($this->status !== $status) {
            $this->status = $status;
            $this->save();
        }
    }
}