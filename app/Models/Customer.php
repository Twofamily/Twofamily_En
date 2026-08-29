<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CustomerAddress;

class Customer extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'name_customer',
        'customer_type',
        'phone_customer',
        'email_customer',
        'address_detail',
        'subdistrict',
        'district',
        'province',
        'zipcode',
    ];

    public function camps()
{
    return $this->hasMany(Camp::class, 'id_customer', 'id_customer');
}
}
