<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id_driver';

    protected $fillable = [
        'fname_driver',
        'lname_driver',
        'address_no',      
        'moo',             
        'address_detail',
        'subdistrict',
        'district',
        'province',
        'zipcode',
        'phone_driver',
        'citizenid_driver',
        'citizen_image',
    ];
}