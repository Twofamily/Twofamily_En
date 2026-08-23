<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'truck_brand_id',
        'name_model',
        'model_year',
        'truck_type',
        'wheels',
        'load_capacity',
        'cubic_capacity',
        'curb_weight',
        'tank_capacity',
        'engine_cc',
        'horsepower',
        'fuel_type',
        'fuel_rate',
        'is_active',
    ];

        protected $casts = [
        'model_year'     => 'integer',
        'wheels'         => 'integer',
        'load_capacity'  => 'integer',
        'cubic_capacity' => 'decimal:2',
        'curb_weight'    => 'integer',
        'tank_capacity'  => 'integer',
        'engine_cc'      => 'integer',
        'horsepower'     => 'integer',
        'fuel_rate'      => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(TruckBrand::class, 'truck_brand_id');
    }

    public function trucks()
    {
        return $this->hasMany(Truck::class, 'truck_model_id');
    }

    public function getFullNameAttribute()
    {
        $name = $this->name_model;

        if ($this->model_year) {
            $name .= ' (' . $this->model_year . ')';
        }

        if ($this->wheels) {
            $name .= ' - ' . $this->wheels . ' ล้อ';
        }

        return $name;
    }
}