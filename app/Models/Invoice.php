<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $primaryKey = 'id_invoice';

    protected $fillable = [
        'id_quotation',
        'id_delivery_note',
        'id_customer',
        'discount',
        'total',
        'status'
    ];

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
}
