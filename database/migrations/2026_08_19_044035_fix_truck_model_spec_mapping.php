<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ย้ายค่าที่ลงผิดช่อง: engine_cc จริง ๆ คือน้ำหนักรถเปล่า
        DB::statement("
            UPDATE truck_models
            SET curb_weight = engine_cc,
                engine_cc   = NULL
            WHERE engine_cc IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE truck_models
            SET engine_cc   = curb_weight,
                curb_weight = NULL
            WHERE curb_weight IS NOT NULL
        ");
    }
};