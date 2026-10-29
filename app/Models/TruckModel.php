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

    /**
     * สเปคทั้งหมดของรุ่น พร้อมหน่วย ในรูป [ป้าย => ค่าที่แสดง]
     * ใช้ในหน้าต่าง "ดู" ของแท็บรุ่นรถ (ไม่มีหน้า show แยก)
     */
    public function specList(): array
    {
        $num = fn ($v, string $unit, int $dec = 0) => $v === null || $v === ''
            ? '-'
            : number_format((float) $v, $dec) . ' ' . $unit;

        return [
            'ยี่ห้อ'              => $this->brand->name_brand ?? '-',
            'ชื่อรุ่น'            => $this->name_model,
            'ปีรุ่น'              => $this->model_year ?? '-',
            'ประเภท'             => $this->truck_type ?: '-',
            'จำนวนล้อ'           => $num($this->wheels, 'ล้อ'),
            'ความจุกระบะ'        => $num($this->cubic_capacity, 'คิว', 2),
            'น้ำหนักบรรทุก'      => $num($this->load_capacity, 'กก.'),
            'น้ำหนักรถเปล่า'     => $num($this->curb_weight, 'กก.'),
            'ความจุถังน้ำมัน'    => $num($this->tank_capacity, 'ลิตร'),
            'อัตราสิ้นเปลือง'    => $num($this->fuel_rate, 'กม./ลิตร', 2),
            'เชื้อเพลิง'          => $this->fuel_type ?: '-',
            'ขนาดเครื่องยนต์'    => $num($this->engine_cc, 'ซีซี'),
            'แรงม้า'             => $num($this->horsepower, 'แรงม้า'),
            'สถานะ'              => $this->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน',
        ];
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