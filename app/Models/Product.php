<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'id_product';

    protected $fillable = [
        'name_product',
        'detail_product',
        'unit_price',
        'product_type_id',
        'image',
    ];

    public function type()
    {
        return $this->belongsTo(
            ProductType::class,
            'product_type_id',
            'id_product_type'
        );
    }
}