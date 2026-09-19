<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    protected $table      = 'sales_order_details';
    protected $primaryKey = 'id_sod';

    protected $fillable = [
        'id_so',
        'id_product',
        'quantity',
        'price_per_unit',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'decimal:2',
            'price_per_unit' => 'decimal:2',
            'total_price'    => 'decimal:2',
        ];
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'id_so', 'id_so');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}