<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ย้ายค่าตั้งค่าจาก key ชื่อเดิม (company_name, bank_account, ...)
 * ไปใส่ key ใหม่ตามทะเบียนใน config/settings.php
 *
 * - คัดลอกเท่านั้น ไม่ลบ key เดิม เพราะ PDF ยังอ่าน key เดิมอยู่
 * - ถ้า key ใหม่มีค่าอยู่แล้ว จะไม่ทับ (รันซ้ำได้ปลอดภัย)
 * - ถ้า key ใหม่มี legacy หลายตัว จะใช้ตัวแรกที่มีค่า
 */
return new class extends Migration {
    public function up(): void
    {
        $stored = DB::table('settings')->pluck('value', 'key');

        foreach (config('settings.definitions', []) as $key => $definition) {
            if ($stored->has($key)) {
                continue;
            }

            foreach ($definition['legacy'] ?? [] as $legacyKey) {
                $value = $stored->get($legacyKey);

                if ($value === null || $value === '') {
                    continue;
                }

                DB::table('settings')->insert([
                    'key'        => $key,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                break;
            }
        }

        // เขียนผ่าน DB::table ตรง ๆ event ของ model จึงไม่ทำงาน ต้องล้าง cache เอง
        Setting::flushCache();
    }

    public function down(): void
    {
        // ไม่ย้อนกลับ: key เดิมยังอยู่ครบ ไม่มีข้อมูลหาย
    }
};