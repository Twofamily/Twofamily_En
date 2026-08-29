<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id_quot';

    protected $fillable = [
        'id_customer',
        'date_quot',
        'end_quot',
        'status',
        'subtotal',
        'discount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'date_quot'    => 'date',
            'end_quot'     => 'date',
            'subtotal'     => 'decimal:2',
            'discount'     => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

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

    public function getCodeQuotAttribute(): string
    {
        return 'QT' . str_pad($this->id_quot, 5, '0', STR_PAD_LEFT);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'id_quot', 'id_quot');
    }

    /** ใบเสนอราคา 1 ใบ → เปิดได้หลายแคมป์ */
    public function camps()
    {
        return $this->hasMany(Camp::class, 'id_quot', 'id_quot');
    }

        public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_quotation', 'id_quot');
    }
}