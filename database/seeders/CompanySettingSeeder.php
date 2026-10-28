<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * ข้อมูลบริษัท / การชำระเงิน ที่ PDF ต้องใช้
 *
 * ใส่เฉพาะช่องที่ยังว่าง — ถ้าเคยแก้ในหน้าตั้งค่าแล้ว จะไม่ทับค่าเดิม
 * รันซ้ำได้ปลอดภัย: php artisan db:seed --class=CompanySettingSeeder
 *
 * ⚠ เลขผู้เสียภาษี / เลขบัญชี เป็นค่าตัวอย่าง ให้แก้เป็นของจริงในหน้า ตั้งค่าระบบ
 */
class CompanySettingSeeder extends Seeder
{
    private const VALUES = [
        'company.name_th' => 'บริษัท ทู แฟมิลี่ เอ็นจิเนียริ่ง จำกัด',
        'company.name_en' => 'Two Family Engineering Co., Ltd.',
        'company.tax_id'  => '0000000000000',
        'company.branch'  => 'สำนักงานใหญ่',
        'company.address' => '189 หมู่ที่ 14 ตำบลสูงเนิน อำเภอสูงเนิน จังหวัดนครราชสีมา 30170',
        'company.phone'   => '02-123-4567',
        'company.email'   => 'contact@twofamily.co.th',

        'payment.bank_name'    => 'ธนาคารกสิกรไทย สาขาสูงเนิน',
        'payment.account_name' => 'บจก. ทู แฟมิลี่ เอ็นจิเนียริ่ง',
        'payment.account_no'   => '000-0-00000-0',

        'doc.credit_days' => 30,
    ];

    public function run(): void
    {
        $stored = Setting::query()->pluck('value', 'key');

        // เติมเฉพาะ key ที่ยังไม่มี หรือมีแต่ว่าง
        $missing = collect(self::VALUES)
            ->filter(fn ($value, $key) => blank($stored->get($key)))
            ->all();

        if ($missing) {
            Setting::setMany($missing);   // เขียน key ชื่อเดิม (company_name ฯลฯ) ให้ PDF ด้วย
        }

        $this->command?->info('ตั้งค่าข้อมูลบริษัท: เติม ' . count($missing) . ' ช่อง (ช่องที่มีค่าอยู่แล้วไม่ถูกแก้)');
    }
}
