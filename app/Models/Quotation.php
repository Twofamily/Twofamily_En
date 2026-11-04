<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes, HasDocumentCode;

    protected $primaryKey = 'id_quot';

    protected $fillable = [
        'code_quot',
        'id_customer',
        'date_quot',
        'end_quot',
        'status',
        'issued_by_user_id',
        'issued_at',
        'approved_by_user_id',
        'approved_at',
        'subtotal',
        'discount',
        'vat_rate',
        'vat_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'date_quot'    => 'date',
            'end_quot'     => 'date',
            'issued_at'    => 'datetime',
            'approved_at'  => 'datetime',
            'subtotal'     => 'decimal:2',
            'discount'     => 'decimal:2',
            'vat_rate'     => 'decimal:2',
            'vat_amount'   => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public const DEFAULT_VAT_RATE = 7.00;

    public const STATUS_LABELS = [
        'draft'    => 'ร่าง',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ยกเลิก',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'ไม่ทราบสถานะ';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'id_quot', 'id_quot');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id', 'id');
    }

    public function approveBlocker(?User $user): ?string
    {
        if (! $user || ! $user->isAdmin()) {
            return 'อนุมัติได้เฉพาะผู้ดูแลระบบ / ผู้จัดการ';
        }

        if ($this->status !== 'draft') {
            return 'อนุมัติได้เฉพาะใบเสนอราคาที่เป็นฉบับร่าง';
        }

        $isOwnDocument = (int) $this->issued_by_user_id === (int) $user->getKey();
        $otherAdmins   = User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($isOwnDocument && $otherAdmins) {
            return 'คุณเป็นผู้จัดทำใบนี้ ต้องให้ผู้อื่นอนุมัติ';
        }

        return null;
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id', 'id');
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'id_quot', 'id_quot');
    }

    public function camp()
    {
        return $this->hasOneThrough(
            Camp::class,
            SalesOrder::class,
            'id_quot',
            'id_camp',
            'id_quot',
            'id_camp'
        );
    }

    public function camps()
    {
        return $this->hasMany(Camp::class, 'id_quot', 'id_quot');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_quotation', 'id_quot');
    }

    public function getAfterDiscountAttribute(): float
    {
        return max((float) $this->subtotal - (float) $this->discount, 0);
    }

    public function canCreateSalesOrder(): bool
    {
        return $this->status === 'approved' && ! $this->hasSalesOrder();
    }

    public function hasSalesOrder(): bool
    {
        return $this->salesOrder()->exists();
    }

    public function isExpired(): bool
    {
        return $this->end_quot && $this->end_quot->isPast();
    }

    public function scopeConvertible($query)
    {
        return $query->where('status', 'approved')
                     ->whereDoesntHave('salesOrder');
    }
}