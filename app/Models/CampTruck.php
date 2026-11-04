<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CampTruck extends Pivot
{
    protected $table      = 'camp_truck';
    protected $primaryKey = 'id_assignment';
    public $incrementing  = true;

    protected $casts = [
        'assigned_date' => 'date',
        'released_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'id_driver', 'id_driver');
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class, 'id_truck', 'id_truck');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    public function isActive(): bool
    {
        return $this->released_date === null;
    }
}
