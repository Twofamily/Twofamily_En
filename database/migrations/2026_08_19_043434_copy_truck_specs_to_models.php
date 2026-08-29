<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ดึงค่าสเปคจากรถที่มีอยู่ ขึ้นไปเก็บที่ระดับรุ่น
        // ใช้ MAX() เผื่อรถรุ่นเดียวกันกรอกมาไม่เท่ากัน จะได้ค่าที่มากที่สุด
        $specifications = DB::table('trucks')
            ->selectRaw('truck_model_id, MAX(weight_truck) as load_capacity, MAX(fuelfactory_truck) as engine_cc, MAX(fuel_rate) as fuel_rate')
            ->whereNotNull('truck_model_id')
            ->whereNull('deleted_at')
            ->groupBy('truck_model_id')
            ->get();

        foreach ($specifications as $specification) {
            foreach (['load_capacity', 'engine_cc', 'fuel_rate'] as $column) {
                DB::table('truck_models')
                    ->where('id', $specification->truck_model_id)
                    ->whereNull($column)
                    ->update([$column => $specification->{$column}]);
            }
        }
    }

    public function down(): void
    {
        // ไม่ต้องย้อน เพราะข้อมูลต้นทางยังอยู่ที่ trucks
    }
};
