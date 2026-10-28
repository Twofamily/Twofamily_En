<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ใบเสร็จรับเงิน — ออกได้ 3 แบบ ไม่ต้องรอเอกสารใบอื่นครบ
 *
 *   invoice : รับชำระตามใบแจ้งหนี้ (ส่งของแล้ว แจ้งหนี้แล้ว ค่อยจ่าย)
 *   advance : รับเงินล่วงหน้า ผูกแคมป์ (ลูกค้าจ่ายก่อนส่งของ)
 *   direct  : ขายสด (ลูกค้า walk-in ซื้อแล้วจ่ายทันที ไม่ต้องมีแคมป์)
 *
 * ทุกแบบต้องมีหลักฐานการชำระ
 */
class Receipt extends Model
{
    use HasDocumentCode;

    protected $primaryKey = 'id_receipt';

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_ADVANCE = 'advance';
    public const TYPE_DIRECT  = 'direct';

    public const TYPES = [
        self::TYPE_INVOICE => ['label' => 'ชำระตามใบแจ้งหนี้', 'badge' => 'primary'],
        self::TYPE_ADVANCE => ['label' => 'รับเงินล่วงหน้า',   'badge' => 'warning'],
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
        'advance_deducted',
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
            'advance_deducted' => 'decimal:2',
            'total'            => 'decimal:2',
        ];
    }

    protected $attributes = [
        'receipt_type' => self::TYPE_INVOICE,
    ];

    /* ==================== Relations ==================== */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    /** รายการของใบเสร็จ advance / direct (แบบ invoice ใช้รายการของใบแจ้งหนี้) */
    public function items()
    {
        return $this->hasMany(ReceiptItem::class, 'id_receipt', 'id_receipt');
    }

    /** หลักฐานการชำระของใบเสร็จ advance / direct */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'id_receipt', 'id_receipt')
                    ->orderBy('paid_at')
                    ->orderBy('id_payment');
    }

    /** ใบเสร็จล่วงหน้าใบนี้ ถูกหักไปใช้ในใบแจ้งหนี้ใบไหนบ้าง */
    public function appliedAdvances()
    {
        return $this->hasMany(Payment::class, 'id_advance_receipt', 'id_receipt');
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

    public function isAdvance(): bool
    {
        return $this->receipt_type === self::TYPE_ADVANCE;
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

    /* ==================== เงินรับล่วงหน้า ==================== */

    /** ถูกหักไปใช้แล้วเท่าไร (เฉพาะใบเสร็จล่วงหน้า) */
    public function advanceUsed(): float
    {
        if (! $this->isAdvance()) {
            return 0;
        }

        $sum = $this->relationLoaded('appliedAdvances')
            ? $this->appliedAdvances->sum(fn ($p) => (float) $p->amount)
            : (float) $this->appliedAdvances()->sum('amount');

        return round($sum, 2);
    }

    /** เหลือให้หักได้อีกเท่าไร */
    public function advanceRemaining(): float
    {
        if (! $this->isAdvance()) {
            return 0;
        }

        return round(max((float) $this->total - $this->advanceUsed(), 0), 2);
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
     * invoice → หลักฐานในใบแจ้งหนี้ (ไม่รวมรายการหักเงินล่วงหน้า)
     * อื่น ๆ  → หลักฐานที่แนบตอนออกใบเสร็จ
     */
    public function proofPayments(): Collection
    {
        if ($this->isInvoiceType()) {
            return ($this->invoice?->payments ?? collect())
                ->reject(fn (Payment $p) => $p->isAdvanceApplication())
                ->values();
        }

        return $this->payments;
    }

    /** วิธีชำระหลักของใบเสร็จ ไว้ติ๊กช่องใน PDF */
    public function mainMethod(): ?string
    {
        return $this->proofPayments()->first()?->method;
    }

    /* ==================== กฎการลบ ==================== */

    /** ใบเสร็จล่วงหน้าที่ถูกหักไปแล้ว ห้ามลบ (ต้องลบรายการหักในใบแจ้งหนี้ก่อน) */
    public function canDelete(): bool
    {
        return ! ($this->isAdvance() && $this->advanceUsed() > 0);
    }

    /* ==================== คำนวณ VAT ==================== */

    /**
     * แยกยอดรวม VAT ออกเป็น [ก่อน VAT, VAT]
     * ใช้กับเงินรับล่วงหน้า ที่ลูกค้าโอนมาเป็นยอดรวม
     */
    public static function splitGross(float $gross, float $vatRate = Invoice::VAT_RATE): array
    {
        $subtotal = round($gross * 100 / (100 + $vatRate), 2);

        return [$subtotal, round($gross - $subtotal, 2)];
    }

    /* ==================== Scopes ==================== */

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->where('receipt_type', $type) : $query;
    }
}
