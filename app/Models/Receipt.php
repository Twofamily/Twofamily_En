<?php

namespace App\Models;

use App\Models\Concerns\Cancellable;
use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Receipt extends Model
{
    use HasDocumentCode, Cancellable;

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

    public function items()
    {
        return $this->hasMany(ReceiptItem::class, 'id_receipt', 'id_receipt');
    }

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

    public function payer(): ?Customer
    {
        return $this->customer ?? $this->invoice?->customer;
    }

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

    public function proofPayments(): Collection
    {
        if ($this->isInvoiceType()) {
            return ($this->invoice?->payments ?? collect())->values();
        }

        return $this->payments;
    }

    public function mainMethod(): ?string
    {
        return $this->proofPayments()->first()?->method;
    }

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

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->where('receipt_type', $type) : $query;
    }
}
