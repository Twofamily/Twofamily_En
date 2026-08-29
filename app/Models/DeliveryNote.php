<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    protected $primaryKey = 'id_delivery_note';

    protected $fillable = [
        'id_quotation',
        'id_camp',
        'id_customer',
        'delivery_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
        ];
    }

    public function getCodeDeliveryAttribute(): string
    {
        return 'DN' . str_pad($this->id_delivery_note, 5, '0', STR_PAD_LEFT);
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
        return $this->hasOne(Invoice::class, 'id_delivery_note', 'id_delivery_note');
    }
}