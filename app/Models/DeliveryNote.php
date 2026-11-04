<?php

namespace App\Models;

use App\Models\Concerns\Cancellable;
use App\Models\Concerns\HasDocumentCode;
use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasDocumentCode, Cancellable, RecordsCreator;

    public const DELETED_AT = 'cancelled_at';

    protected $primaryKey = 'id_delivery_note';

    protected $fillable = [
        'created_by_user_id',
        'code_dn',
        'id_quotation',
        'id_camp',
        'id_customer',
        'id_invoice',
        'delivery_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'cancelled_at'  => 'datetime',
        ];
    }

    public function getCodeDeliveryAttribute(): string
    {
        return $this->attributes['code_dn'] ?? '';
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quotation', 'id_quot');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function details()
    {
        return $this->hasMany(DeliveryNoteDetail::class, 'id_delivery_note', 'id_delivery_note');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    public function subtotal(): float
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->get();

        return round($details->sum(fn ($d) => (float) $d->total_price), 2);
    }

    public function cancelBlocker(): ?string
    {
        if ($this->isCancelled()) {
            return 'ใบส่งของนี้ถูกยกเลิกไปแล้ว';
        }
        if ($this->id_invoice && $this->invoice) {
            return 'ออกใบแจ้งหนี้ ' . $this->invoice->code_inv . ' แล้ว ต้องยกเลิกใบแจ้งหนี้ก่อน';
        }

        return null;
    }

    public function scopePendingInvoice($query)
    {
        return $query->whereNull('id_invoice');
    }
}