<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * ข้อมูลตัวอย่างสำหรับเดโม (ลูกค้า แคมป์ เอกสารครบทั้งสาย) แยกไว้ ไม่รันอัตโนมัติ:
     *   php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,       // ผู้ดูแลระบบ — ต้องมาก่อนเสมอ
            CompanySettingSeeder::class,  // ข้อมูลบริษัทที่ PDF ต้องใช้
            ProductTypesSeeder::class,    // ประเภทสินค้า
            TruckModelSeeder::class,      // ยี่ห้อ + รุ่นรถบรรทุก
        ]);
    }
}
