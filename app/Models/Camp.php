<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camp extends Model
{
    use SoftDeletes;

    protected $table      = 'camps';
    protected $primaryKey = 'id_camp';

    protected $fillable = [
        'id_customer',
        'id_quot',
        'code_camp',
        'name_camp',
        'address_detail',
        'subdistrict',
        'district',
        'province',
        'zipcode',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'status_camp',
        'note',
    ];

    public const STATUS_LABELS = [
        'active' => 'เปิดใช้งาน',
        'closed' => 'ปิดแคมป์แล้ว',
    ];

    public function getRouteKeyName()
    {
        return 'id_camp';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    /** แคมป์นี้เปิดมาจากใบเสนอราคาใบไหน */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quot', 'id_quot');
    }

        public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_camp', 'id_camp');
    }

    public function scopeActive($query)
    {
        return $query->where('status_camp', 'active');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status_camp] ?? '-';
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_detail,
            $this->subdistrict ? 'ต.' . $this->subdistrict : null,
            $this->district    ? 'อ.' . $this->district : null,
            $this->province    ? 'จ.' . $this->province : null,
            $this->zipcode,
        ])->filter()->implode(' ');
    }

    public static function generateCode(): string
    {
        $prefix = 'CP-' . (now()->year + 543) . '-';

        $last = static::withTrashed()
            ->where('code_camp', 'like', $prefix . '%')
            ->orderByDesc('code_camp')
            ->value('code_camp');

        $running = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($running, 4, '0', STR_PAD_LEFT);
    }

    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'camp_truck', 'id_camp', 'id_truck')
            ->withPivot('id_assignment', 'assigned_date', 'released_date', 'note')
            ->withTimestamps();
    }

    public function activeTrucks()
    {
        return $this->trucks()->wherePivotNull('released_date');
    }
}