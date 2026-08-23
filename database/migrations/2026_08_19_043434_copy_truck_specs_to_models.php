<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ดึงค่าสเปคจากรถที่มีอยู่ ขึ้นไปเก็บที่ระดับรุ่น
        // ใช้ MAX() เผื่อรถรุ่นเดียวกันกรอกมาไม่เท่ากัน จะได้ค่าที่มากที่สุด
        DB::statement("
            UPDATE truck_models tm
            INNER JOIN (
                SELECT truck_model_id,
                       MAX(weight_truck)       AS w,
                       MAX(fuelfactory_truck)  AS ff,
                       MAX(fuel_rate)          AS fr
                FROM trucks
                WHERE truck_model_id IS NOT NULL
                  AND deleted_at IS NULL
                GROUP BY truck_model_id
            ) t ON t.truck_model_id = tm.id
            SET tm.load_capacity = COALESCE(tm.load_capacity, t.w),
                tm.engine_cc     = COALESCE(tm.engine_cc, t.ff),
                tm.fuel_rate     = COALESCE(tm.fuel_rate, t.fr)
        ");
    }

    public function down(): void
    {
        // ไม่ต้องย้อน เพราะข้อมูลต้นทางยังอยู่ที่ trucks
    }
};