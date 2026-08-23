<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use SoftDeletes;

    protected $table = 'trucks';
    protected $primaryKey = 'id_truck';
    public $incrementing = false;
    protected $keyType = 'string';

        protected $fillable = [
        'id_truck',
        'truck_brand_id',
        'truck_model_id',
        'year_truck',
        'province_truck',
        'fuel_rate',
        'weight_truck',
        'fuelfactory_truck',
        'cubic_capacity',
        'status_truck',
    ];

        protected $casts = [
        'fuel_rate'      => 'decimal:2',
        'cubic_capacity' => 'decimal:2',
        'year_truck'     => 'integer',
    ];

    public const STATUS_LABELS = [
        'active'      => 'พร้อมใช้งาน',
        'maintenance' => 'ซ่อมบำรุง',
        'retired'     => 'ปลดประจำการ',
    ];

    public function getRouteKeyName()
    {
        return 'id_truck';
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => self::STATUS_LABELS[$this->status_truck] ?? '-',
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status_truck) {
                'active'      => 'success',
                'maintenance' => 'warning',
                'retired'     => 'secondary',
                default       => 'light',
            },
        );
    }

    public function scopeAvailable($query)
    {
        return $query->where('status_truck', 'active');
    }


    public function brand()
    {
        return $this->belongsTo(TruckBrand::class, 'truck_brand_id');
    }

    public function model()
    {
        return $this->belongsTo(TruckModel::class, 'truck_model_id');
    }

    public function fuelRecords()
    {
        return $this->hasMany(FuelRecord::class, 'trucks_id_truck', 'id_truck');
    }

    public function maintenances()
    {
        return $this->hasMany(TruckMaintenance::class, 'id_truck', 'id_truck')
                    ->orderByDesc('start_date');
    }

    public function ongoingMaintenance()
    {
        return $this->hasOne(TruckMaintenance::class, 'id_truck', 'id_truck')
                    ->whereNull('finished_date')
                    ->latestOfMany('start_date');
    }

    public function camps()
    {
        return $this->belongsToMany(Camp::class, 'camp_truck', 'id_truck', 'id_camp')
                    ->withPivot('id_assignment', 'assigned_date', 'released_date', 'note')
                    ->withTimestamps();
    }
}