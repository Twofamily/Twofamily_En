<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * เปลี่ยนชื่อ route จาก sales-orders / delivery-notes เป็น sales_orders / delivery_notes
 * สิทธิ์รายหน้าที่บันทึกไว้ใน users.permissions ยังใช้คีย์ชื่อเดิม → ระบบหาไม่เจอแล้วถอยไปใช้สิทธิ์ตาม role
 * migration นี้แปลงคีย์ให้ สิทธิ์ที่เคยตั้งไว้จึงกลับมาใช้ได้เหมือนเดิม
 */
return new class extends Migration
{
    private const MAP = [
        'sales-orders'   => 'sales_orders',
        'delivery-notes' => 'delivery_notes',
    ];

    public function up(): void
    {
        $this->rename(self::MAP);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::MAP));
    }

    private function rename(array $map): void
    {
        DB::table('users')->whereNotNull('permissions')->orderBy('id')->each(function ($user) use ($map) {
            $perms = json_decode($user->permissions, true);

            if (! is_array($perms)) {
                return;
            }

            $changed = false;
            foreach ($map as $old => $new) {
                if (array_key_exists($old, $perms)) {
                    // มีคีย์ใหม่อยู่แล้ว (ตั้งหลังเปลี่ยนชื่อ) → ใช้ค่าใหม่ ทิ้งค่าเก่า
                    $perms[$new] ??= $perms[$old];
                    unset($perms[$old]);
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('users')->where('id', $user->id)->update(['permissions' => json_encode($perms)]);
            }
        });
    }
};
