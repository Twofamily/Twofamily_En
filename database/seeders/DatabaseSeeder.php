<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,     // ผู้ดูแลระบบ — ต้องมาก่อนเสมอ
            ProductTypesSeeder::class,  // ประเภทสินค้า
            TruckModelSeeder::class,    // ยี่ห้อ + รุ่นรถบรรทุก
        ]);
    }
}