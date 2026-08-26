<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelRecord extends Model
{
    use SoftDeletes;

    protected $table = 'fuel_records';

    protected $primaryKey = 'id_fuel_record';

    protected $fillable = [
        'date_record',
        'start_point',
        'start_detail',
        'destination',
        'destination_detail',
        'age_truck',
        'depreciation',
        'current_weight',
        'max_load',
        'distance',
        'cost_fuel',
        'trucks_id_truck',
        'cost_fuel_total',
        'total_fuel_liters',
    ];

    protected $casts = [
        'date_record' => 'date',
        'current_weight' => 'decimal:2',
        'max_load' => 'decimal:2',
        'distance' => 'decimal:2',
        'cost_fuel' => 'decimal:2',
        'cost_fuel_total' => 'decimal:2',
        'total_fuel_liters' => 'decimal:2',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(
            Truck::class,
            'trucks_id_truck',
            'id_truck'
        );
    }

    public function segments(): HasMany
    {
        return $this->hasMany(
            FuelRecordSegment::class,
            'fuel_record_id',
            'id_fuel_record'
        )->orderBy('sequence');
    }
}