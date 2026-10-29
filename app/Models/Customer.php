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
        'name_prefix',
        'first_name',
        'last_name',
        'tax_id',
        'branch_code',
        'contact_name',
        'phone_customer',
        'email_customer',
        'address_no',
        'moo',
        'address_detail',
        'soi',
        'road',
        'subdistrict',
        'district',
        'province',
        'zipcode',
    ];

    // is_walk_in ไม่อยู่ใน $fillable — ตั้งได้จาก walkIn() เท่านั้น กันฟอร์มส่งค่ามาแก้
    protected $casts = [
        'is_walk_in' => 'boolean',
    ];

    public const TYPES = [
        'person'  => 'บุคคล',
        'company' => 'บริษัท / นิติบุคคล',
    ];

    /** คำนำหน้าชื่อที่เลือกได้ในฟอร์ม (ลูกค้าบุคคล) */
    public const PREFIXES = ['นาย', 'นาง', 'นางสาว'];

    /** รหัสสาขาของสำนักงานใหญ่ ตามแบบที่กรมสรรพากรใช้ */
    public const HEAD_OFFICE = '00000';

    /** ชื่อลูกค้าสำหรับขายสดหน้าร้านที่ไม่ต้องการลงทะเบียน */
    public const WALK_IN_NAME = 'ลูกค้าทั่วไป (ไม่ระบุชื่อ)';

    /** ชื่อเดิมก่อนเปลี่ยน (ใช้ใน migration เปลี่ยนชื่อแถวที่มีอยู่แล้ว) */
    public const WALK_IN_OLD_NAME = 'ลูกค้าทั่วไป (เงินสด)';

    /**
     * name_customer = ชื่อที่แสดงทั้งระบบ
     *   บุคคล  → รวมจาก คำนำหน้า + ชื่อ + นามสกุล ให้อัตโนมัติ
     *   บุคคลที่บันทึกมาแบบชื่อเต็มช่องเดียว (เพิ่มลูกค้าด่วนในหน้าใบเสนอราคา/ใบเสร็จ) → แยกเก็บลงช่องย่อยให้
     */
    protected static function booted(): void
    {
        static::saving(function (Customer $customer) {
            if ($customer->customer_type !== 'person') {
                return;
            }

            $partsChanged = $customer->isDirty(['name_prefix', 'first_name', 'last_name']);

            if ($partsChanged && filled($customer->first_name)) {
                $customer->name_customer = self::joinName(
                    $customer->name_prefix, $customer->first_name, $customer->last_name
                );
            } elseif ($customer->isDirty('name_customer') && blank($customer->first_name)
                      && ! $customer->is_walk_in) {
                [$customer->name_prefix, $customer->first_name, $customer->last_name]
                    = self::splitName((string) $customer->name_customer);
            }
        });
    }

    /** "นาย" + "สมชาย" + "ใจดี" → "นายสมชาย ใจดี" (คำนำหน้าติดกับชื่อตามแบบที่เขียนกันทั่วไป) */
    public static function joinName(?string $prefix, ?string $first, ?string $last): string
    {
        return trim(trim((string) $prefix) . trim((string) $first) . ' ' . trim((string) $last));
    }

    /** "นายสมชาย ใจดี" → ['นาย', 'สมชาย', 'ใจดี'] */
    public static function splitName(string $name): array
    {
        $name   = trim(preg_replace('/\s+/u', ' ', $name));
        $prefix = null;

        foreach (['นางสาว', 'นาง', 'นาย', 'น.ส.'] as $p) {
            if (str_starts_with($name, $p) && mb_strlen($name) > mb_strlen($p)) {
                $prefix = $p === 'น.ส.' ? 'นางสาว' : $p;
                $name   = ltrim(mb_substr($name, mb_strlen($p)));
                break;
            }
        }

        $parts = explode(' ', $name, 2);

        return [$prefix, $parts[0] !== '' ? $parts[0] : null, $parts[1] ?? null];
    }

    public function camps()
    {
        return $this->hasMany(Camp::class, 'id_customer', 'id_customer');
    }

    /**
     * ลูกค้าทั่วไป — มีแถวเดียวในระบบ (is_walk_in = true) สร้างให้เองเมื่อใช้ครั้งแรก
     * ใช้กับขายสดที่ลูกค้าไม่แจ้งชื่อ/ไม่ต้องการใบกำกับภาษีเต็มรูป
     * ดูจากคอลัมน์ ไม่ดูจากชื่อ → เปลี่ยนชื่อที่แสดงได้โดยไม่เกิดแถวซ้ำ
     */
    public static function walkIn(): self
    {
        $customer = static::withTrashed()->where('is_walk_in', true)->orderBy('id_customer')->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            return $customer;
        }

        $customer = new static(['name_customer' => self::WALK_IN_NAME, 'customer_type' => 'person']);
        $customer->is_walk_in = true;
        $customer->save();

        return $customer;
    }

    public function isWalkIn(): bool
    {
        return (bool) $this->is_walk_in;
    }

    /** ลูกค้าจริง (ไม่รวมลูกค้าทั่วไปของระบบ) — ใช้ในหน้ารายชื่อลูกค้า */
    public function scopeRegular($query)
    {
        return $query->where('is_walk_in', false);
    }

    public function isCompany(): bool
    {
        return $this->customer_type === 'company';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->customer_type] ?? '-';
    }

    /** "สำนักงานใหญ่" / "สาขาที่ 00002" / null (บุคคล หรือยังไม่ระบุ) */
    public function branchLabel(): ?string
    {
        if (! $this->isCompany() || blank($this->branch_code)) {
            return null;
        }

        return $this->branch_code === self::HEAD_OFFICE ? 'สำนักงานใหญ่' : 'สาขาที่ ' . $this->branch_code;
    }

    /** เลขผู้เสียภาษีแบบอ่านง่าย 0-1055-12345-67-8 */
    public function formattedTaxId(): ?string
    {
        $id = preg_replace('/\D/', '', (string) $this->tax_id);

        if (strlen($id) !== 13) {
            return $this->tax_id ?: null;
        }

        return substr($id, 0, 1) . '-' . substr($id, 1, 4) . '-' . substr($id, 5, 5)
             . '-' . substr($id, 10, 2) . '-' . substr($id, 12, 1);
    }
}
