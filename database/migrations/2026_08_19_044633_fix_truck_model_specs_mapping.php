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
        DB::statement("
            UPDATE truck_models tm
            INNER JOIN (
                SELECT truck_model_id,
                       MAX(weight_truck)      AS cw,
                       MAX(fuelfactory_truck) AS tank,
                       MAX(fuel_rate)         AS fr
                FROM trucks
                WHERE truck_model_id IS NOT NULL
                  AND deleted_at IS NULL
                GROUP BY truck_model_id
            ) t ON t.truck_model_id = tm.id
            SET tm.curb_weight   = t.cw,
                tm.tank_capacity = t.tank,
                tm.fuel_rate     = t.fr
        ");
    }

    public function down(): void
    {
        Schema::table('truck_models', function (Blueprint $table) {
            $table->dropColumn('tank_capacity');
        });
    }
};