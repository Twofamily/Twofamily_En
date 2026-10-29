<?php

namespace App\Models;

use App\Models\Concerns\Cancellable;
use App\Models\Concerns\HasDocumentCode;
use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasDocumentCode, Cancellable, RecordsCreator;

    /** ยกเลิกเอกสาร = ใส่ cancelled_at (SoftDeletes) ไม่ลบแถวทิ้ง */
    public const DELETED_AT = 'cancelled_at';

    protected $primaryKey = 'id_invoice';

    protected $fillable = [
        'created_by_user_id',
        'code_inv',
        'id_quotation',
        'id_customer',
        'id_camp',
        'discount',
        'total',
        'status',
        'invoice_date',
        'credit_days',
        'due_date',
        'bill_received_date',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'       => 'date',
            'due_date'           => 'date',
            'bill_received_date' => 'date',
            'credit_days'        => 'integer',
            'cancelled_at'       => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // กันพลาด: ถ้าโค้ดส่วนไหนสร้างใบแจ้งหนี้โดยไม่ระบุวันที่ออก ให้ใช้วันนี้
        static::creating(function (Invoice $invoice) {
            $invoice->invoice_date ??= now()->toDateString();

            // ครบกำหนดชำระ = วันที่ออก + เครดิตเทอม (ถ้าไม่ได้กำหนดมาเอง)
            $invoice->credit_days ??= (int) Setting::get('doc.credit_days', 30);
            $invoice->due_date ??= \Illuminate\Support\Carbon::parse($invoice->invoice_date)
                ->addDays($invoice->credit_days)
                ->toDateString();
        });
    }

    // สถานะการชำระ => ป้ายภาษาไทย + สี badge
    public const STATUSES = [
        'unpaid'  => ['label' => 'ยังไม่ชำระ',   'badge' => 'secondary'],
        'partial' => ['label' => 'ชำระบางส่วน', 'badge' => 'warning'],
        'paid'    => ['label' => 'ชำระครบแล้ว', 'badge' => 'success'],
    ];

    /** อัตราภาษีมูลค่าเพิ่ม (%) */
    public const VAT_RATE = 7;

    /* ---------- ยอดเงินของใบแจ้งหนี้ (ไม่มีส่วนลด) ---------- */
    /*
     | ใบแจ้งหนี้ 1 ใบ = ใบส่งของได้หลายใบ (วางบิลรายรอบ) คิดยอดจาก "รายการที่ส่งจริง" ของใบส่งของเหล่านั้น
     | ไม่ดึงยอดรวม/ส่วนลดของใบเสนอราคาทั้งสัญญามาใช้
     | ยอดสุทธิ = รวมรายการ + VAT 7%
     */

    public function itemsSubtotal(): float
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->get();

        return round($details->sum(fn ($d) => (float) $d->total), 2);
    }

    public function itemsVat(): float
    {
        return round($this->itemsSubtotal() * self::VAT_RATE / 100, 2);
    }

    public function itemsNetTotal(): float
    {
        return round($this->itemsSubtotal() + $this->itemsVat(), 2);
    }

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

    /** แคมป์ของใบแจ้งหนี้ (ใช้สรุปยอดรายแคมป์) */
    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    /** ใบส่งของที่ใบแจ้งหนี้นี้เรียกเก็บ (ได้หลายใบ) */
    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_invoice', 'id_invoice')
                    ->orderBy('delivery_date')
                    ->orderBy('id_delivery_note');
    }

    /* ---------- ครบกำหนดชำระ / วางบิล ---------- */

    /** เลยวันครบกำหนดแล้ว และยังมียอดค้าง */
    public function isOverdue(): bool
    {
        return ! $this->isCancelled()
            && $this->status !== 'paid'
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    /** เลยกำหนดมาแล้วกี่วัน (ยังไม่เลย = 0) */
    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    /** ลูกค้าเซ็นรับบิลแล้วหรือยัง */
    public function isBillReceived(): bool
    {
        return $this->bill_received_date !== null;
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

    /* ---------- ยกเลิก ---------- */

    /**
     * ยกเลิกได้เมื่อยังไม่มีรายการชำระ และยังไม่ออกใบเสร็จ
     * คืนข้อความเหตุผลถ้ายกเลิกไม่ได้ / null ถ้ายกเลิกได้
     */
    public function cancelBlocker(): ?string
    {
        if ($this->isCancelled()) {
            return 'ใบแจ้งหนี้นี้ถูกยกเลิกไปแล้ว';
        }
        if ($this->hasReceipt()) {
            return 'ออกใบเสร็จแล้ว ต้องยกเลิกใบเสร็จก่อน';
        }
        if ($this->hasPayments()) {
            return 'มีรายการชำระเงินอยู่ ต้องลบรายการชำระก่อน';
        }

        return null;
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
        return ! $this->isCancelled()
            && $this->hasPayments()
            && $this->isFullyPaid()
            && ! $this->hasReceipt();
    }

    // เพิ่ม/ลบรายการชำระได้เฉพาะตอนที่ยังไม่ออกใบเสร็จ
    public function canEditPayments(): bool
    {
        return ! $this->isCancelled() && ! $this->hasReceipt();
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