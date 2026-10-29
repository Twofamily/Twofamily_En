<?php

namespace App\Models;

use App\Models\Concerns\Cancellable;
use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ใบเสร็จรับเงิน — ออกได้ 2 แบบ
 *
 *   invoice : รับชำระตามใบแจ้งหนี้ (ส่งของแล้ว ออกใบแจ้งหนี้แล้ว ค่อยจ่าย) — งานแคมป์ทั้งหมดใช้แบบนี้
 *   direct  : ขายสด (ลูกค้า walk-in ซื้อแล้วจ่ายทันที ไม่ต้องมีแคมป์)
 *
 * (เลิกใช้ "รับเงินล่วงหน้า" แล้ว — คอลัมน์ advance_deducted / payments.id_advance_receipt ยังอยู่ในตาราง แต่ไม่ได้ใช้)
 *
 * ทุกแบบต้องมีหลักฐานการชำระ
 */
class Receipt extends Model
{
    use HasDocumentCode, Cancellable;

    /** ยกเลิกเอกสาร = ใส่ cancelled_at (SoftDeletes) ไม่ลบแถวทิ้ง */
    public const DELETED_AT = 'cancelled_at';

    protected $primaryKey = 'id_receipt';

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_DIRECT  = 'direct';

    public const TYPES = [
        self::TYPE_INVOICE => ['label' => 'ชำระตามใบแจ้งหนี้', 'badge' => 'primary'],
        self::TYPE_DIRECT  => ['label' => 'ขายสด',            'badge' => 'success'],
    ];

    protected $fillable = [
        'code_rc',
        'receipt_type',
        'id_invoice',
        'id_customer',
        'id_camp',
        'subtotal',
        'vat_amount',
        'total',
        'date_receipt',
        'description',
        'note',
        'issued_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_receipt'     => 'datetime',
            'subtotal'         => 'decimal:2',
            'vat_amount'       => 'decimal:2',
            'total'            => 'decimal:2',
            'cancelled_at'     => 'datetime',
        ];
    }

    protected $attributes = [
        'receipt_type' => self::TYPE_INVOICE,
    ];

    /* ==================== Relations ==================== */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    /** รายการของใบเสร็จขายสด (แบบ invoice ใช้รายการของใบแจ้งหนี้) */
    public function items()
    {
        return $this->hasMany(ReceiptItem::class, 'id_receipt', 'id_receipt');
    }

    /** หลักฐานการชำระของใบเสร็จขายสด */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'id_receipt', 'id_receipt')
                    ->orderBy('paid_at')
                    ->orderBy('id_payment');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /* ==================== ประเภท ==================== */

    public function isInvoiceType(): bool
    {
        return $this->receipt_type === self::TYPE_INVOICE;
    }

    public function isDirect(): bool
    {
        return $this->receipt_type === self::TYPE_DIRECT;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->receipt_type]['label'] ?? '-';
    }

    public function getTypeBadgeAttribute(): string
    {
        return self::TYPES[$this->receipt_type]['badge'] ?? 'secondary';
    }

    /* ==================== ข้อมูลสำหรับแสดงผล / PDF ==================== */

    /** ลูกค้าของใบเสร็จ (ทุกแบบมี id_customer ของตัวเอง) */
    public function payer(): ?Customer
    {
        return $this->customer ?? $this->invoice?->customer;
    }

    /**
     * รายการบนใบเสร็จ ในรูปแบบเดียวกันทุกประเภท
     * [name, quantity, unit, price, total]
     */
    public function lines(): Collection
    {
        if ($this->isInvoiceType()) {
            return ($this->invoice?->details ?? collect())->map(fn ($d) => [
                'name'     => $d->product->name_product ?? '-',
                'quantity' => (float) $d->quantity,
                'unit'     => 'คิว',
                'price'    => (float) $d->price,
                'total'    => (float) $d->total,
            ]);
        }

        return $this->items->map(fn (ReceiptItem $item) => [
            'name'     => $item->display_name,
            'quantity' => (float) $item->quantity,
            'unit'     => $item->id_product ? 'คิว' : '-',
            'price'    => (float) $item->price,
            'total'    => (float) $item->total,
        ]);
    }

    /**
     * หลักฐานการชำระที่อยู่เบื้องหลังใบเสร็จนี้
     * invoice → หลักฐานในใบแจ้งหนี้
     * direct  → หลักฐานที่แนบตอนออกใบเสร็จ
     */
    public function proofPayments(): Collection
    {
        if ($this->isInvoiceType()) {
            return ($this->invoice?->payments ?? collect())->values();
        }

        return $this->payments;
    }

    /** วิธีชำระหลักของใบเสร็จ ไว้ติ๊กช่องใน PDF */
    public function mainMethod(): ?string
    {
        return $this->proofPayments()->first()?->method;
    }

    /* ==================== กฎการยกเลิก ==================== */

    /** คืนข้อความเหตุผลถ้ายกเลิกไม่ได้ / null ถ้ายกเลิกได้ */
    public function cancelBlocker(): ?string
    {
        if ($this->isCancelled()) {
            return 'ใบเสร็จนี้ถูกยกเลิกไปแล้ว';
        }

        return null;
    }

    public function canCancel(): bool
    {
        return $this->cancelBlocker() === null;
    }

    /* ==================== Scopes ==================== */

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->where('receipt_type', $type) : $query;
    }
}
