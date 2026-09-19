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
        'id_so',
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

    /* ==================== Relations ==================== */

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    /** แคมป์นี้เปิดมาจากใบสั่งขายใบไหน */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'id_so', 'id_so');
    }

    /**
     * ใบเสนอราคาต้นทาง
     * ถ้ามีใบสั่งขายให้วิ่งผ่านใบสั่งขาย ไม่งั้นใช้ id_quot เดิม (แคมป์เก่า)
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quot', 'id_quot');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_camp', 'id_camp');
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

    /* ==================== ยอดตามสัญญา ==================== */

    /**
     * รายการตามสัญญาของแคมป์นี้
     *
     * ใช้เป็นแหล่งเดียวสำหรับคำนวณ "ส่งไปแล้วเท่าไร เหลือเท่าไร"
     * ทั้งใน CampController::buildProgress() และ DeliveryNoteController
     *
     * ลำดับความสำคัญ:
     *   1. ใบสั่งขาย (เส้นทางใหม่)
     *   2. ใบเสนอราคา (แคมป์เก่าที่ยังไม่ได้ migrate)
     *
     * คืนค่าเป็น Collection ที่มี key เหมือนกันทั้งสองทาง
     * เพื่อให้โค้ดปลายทางไม่ต้องแยกเคส
     */
    public function contractItems()
    {
        if ($this->id_so && $this->salesOrder) {
            return $this->salesOrder->details->map(fn($detail) => [
                'id_product'     => $detail->id_product,
                'name_product'   => $detail->product->name_product ?? '-',
                'quantity'       => (float) $detail->quantity,
                'price_per_unit' => (float) $detail->price_per_unit,
            ]);
        }

        if ($this->id_quot && $this->quotation) {
            return $this->quotation->details->map(fn($detail) => [
                'id_product'     => $detail->id_product,
                'name_product'   => $detail->product->name_product ?? '-',
                'quantity'       => (float) $detail->quantity,
                'price_per_unit' => (float) $detail->price_per_unit,
            ]);
        }

        return collect();
    }

    /** แคมป์นี้มีสัญญาผูกอยู่หรือไม่ (ไม่ว่าทางไหน) */
    public function hasContract(): bool
    {
        return (bool) ($this->id_so || $this->id_quot);
    }

    /** เลขเอกสารต้นทางไว้แสดงผล */
    public function getContractCodeAttribute(): ?string
    {
        if ($this->salesOrder) {
            return $this->salesOrder->code_so;
        }

        if ($this->quotation) {
            return $this->quotation->code_quot;
        }

        return null;
    }

    /* ==================== Scopes & Accessors ==================== */

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
}