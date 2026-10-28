<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /** ชื่อลูกค้าสำหรับขายสดหน้าร้านที่ไม่ต้องการลงทะเบียน */
    public const WALK_IN_NAME = 'ลูกค้าทั่วไป (เงินสด)';

    public function camps()
    {
        return $this->hasMany(Camp::class, 'id_customer', 'id_customer');
    }

    /**
     * ลูกค้าทั่วไป — มีแถวเดียวในระบบ สร้างให้เองเมื่อใช้ครั้งแรก
     * ใช้กับขายสดที่ลูกค้าไม่แจ้งชื่อ/ไม่ต้องการใบกำกับภาษีเต็มรูป
     */
    public static function walkIn(): self
    {
        return static::firstOrCreate(
            ['name_customer' => self::WALK_IN_NAME],
            ['customer_type' => 'person']
        );
    }

    public function isWalkIn(): bool
    {
        return $this->name_customer === self::WALK_IN_NAME;
    }
}
