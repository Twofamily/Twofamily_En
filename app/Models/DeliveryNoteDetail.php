<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNoteDetail extends Model
{
    protected $fillable = [
        'id_delivery_note',
        'id_product',
        'quantity',
        'price_per_unit',
        'total_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'id_delivery_note', 'id_delivery_note');
    }
}
