<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เพิ่มคอลัมน์ความจุถังน้ำมัน
        Schema::table('truck_models', function (Blueprint $table) {
            $table->unsignedSmallInteger('tank_capacity')->nullable()->after('fuel_type');
        });

        // ล้างค่าที่แมปผิดในรอบก่อน
        DB::table('truck_models')->update([
            'load_capacity' => null,
            'curb_weight'   => null,
            'engine_cc'     => null,
            'fuel_rate'     => null,
        ]);

        // ดึงใหม่ให้ลงช่องถูก
        $specifications = DB::table('trucks')
            ->selectRaw('truck_model_id, MAX(weight_truck) as curb_weight, MAX(fuelfactory_truck) as tank_capacity, MAX(fuel_rate) as fuel_rate')
            ->whereNotNull('truck_model_id')
            ->whereNull('deleted_at')
            ->groupBy('truck_model_id')
            ->get();

        foreach ($specifications as $specification) {
            DB::table('truck_models')
                ->where('id', $specification->truck_model_id)
                ->update([
                    'curb_weight' => $specification->curb_weight,
                    'tank_capacity' => $specification->tank_capacity,
                    'fuel_rate' => $specification->fuel_rate,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('truck_models', function (Blueprint $table) {
            $table->dropColumn('tank_capacity');
        });
    }
};
