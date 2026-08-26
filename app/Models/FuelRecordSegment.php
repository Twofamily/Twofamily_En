<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelRecordSegment extends Model
{
    protected $table = 'fuel_record_segments';

    protected $fillable = [
        'fuel_record_id',
        'sequence',
        'start_point',
        'destination',
        'load_weight',
        'distance',
        'fuel_rate',
        'fuel_liters',
        'fuel_cost',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'load_weight' => 'decimal:2',
        'distance' => 'decimal:2',
        'fuel_rate' => 'decimal:2',
        'fuel_liters' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
    ];

    public function fuelRecord(): BelongsTo
    {
        return $this->belongsTo(
            FuelRecord::class,
            'fuel_record_id',
            'id_fuel_record'
        );
    }
}