<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasDocumentCode;

    protected $primaryKey = 'id_invoice';

    protected $fillable = [
        'code_inv',
        'id_quotation',
        'id_delivery_note',
        'id_customer',
        'id_camp',
        'discount',
        'total',
        'status',
        'invoice_date',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // กันพลาด: ถ้าโค้ดส่วนไหนสร้างใบแจ้งหนี้โดยไม่ระบุวันที่ออก ให้ใช้วันนี้
        static::creating(function (Invoice $invoice) {
            $invoice->invoice_date ??= now()->toDateString();
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
     | ใบแจ้งหนี้ 1 ใบ = ใบส่งของ 1 ใบ จึงคิดยอดจาก "รายการที่ส่งจริงในใบนี้"
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

    /** แคมป์ของใบแจ้งหนี้ (ใช้หักเงินรับล่วงหน้าของแคมป์ และสรุปยอดรายแคมป์) */
    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
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

    /** ยอดที่หักจากเงินรับล่วงหน้า */
    public function advanceAppliedAmount(): float
    {
        $sum = $this->relationLoaded('payments')
            ? $this->payments->filter(fn ($p) => $p->isAdvanceApplication())
                             ->sum(fn ($p) => $this->toSatang($p->amount))
            : $this->toSatang($this->payments()->where('method', Payment::METHOD_ADVANCE)->sum('amount'));

        return $sum / 100;
    }

    /** ยอดที่ลูกค้าจ่ายเป็นเงินจริงในรอบนี้ (ไม่รวมที่หักจากเงินล่วงหน้า) = ยอดบนใบเสร็จ */
    public function cashPaidAmount(): float
    {
        return ($this->toSatang($this->paidAmount()) - $this->toSatang($this->advanceAppliedAmount())) / 100;
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
    // + ต้องมีเงินจ่ายจริงในรอบนี้ (ถ้าหักเงินล่วงหน้าครบทั้งใบ ใบเสร็จล่วงหน้าคือใบเสร็จของเงินก้อนนั้นแล้ว)
    public function canIssueReceipt(): bool
    {
        return $this->hasPayments()
            && $this->isFullyPaid()
            && ! $this->hasReceipt()
            && $this->cashPaidAmount() > 0;
    }

    /** ชำระครบด้วยเงินรับล่วงหน้าทั้งหมด ไม่ต้องออกใบเสร็จใหม่ */
    public function isSettledByAdvance(): bool
    {
        return $this->isFullyPaid() && $this->hasPayments() && $this->cashPaidAmount() <= 0;
    }

    /** เงินล่วงหน้าของแคมป์ที่ยังหักได้ */
    public function availableAdvance(): float
    {
        return $this->camp ? $this->camp->advanceBalance() : 0;
    }

    /** หักเงินรับล่วงหน้าได้ = ยังไม่ออกใบเสร็จ + ยังมียอดค้าง + แคมป์มีเงินล่วงหน้าเหลือ */
    public function canApplyAdvance(): bool
    {
        return ! $this->hasReceipt()
            && $this->remainingAmount() > 0
            && $this->availableAdvance() > 0;
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