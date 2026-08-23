<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_models', function (Blueprint $table) {
            // ปีรุ่น - สำคัญที่สุด เพราะรุ่นเดียวกันคนละปี สเปคต่างกัน
            $table->year('model_year')->nullable()->after('name_model');

            // ---- สเปคที่จะดึงอัตโนมัติตอนบันทึกรถ ----
            $table->string('truck_type', 50)->nullable()->after('model_year');       // ดัมพ์ / พ่วง / เทรลเลอร์
            $table->unsignedTinyInteger('wheels')->nullable()->after('truck_type');  // 6, 10, 12
            $table->decimal('load_capacity', 8, 2)->nullable()->after('wheels');     // ตัน
            $table->decimal('cubic_capacity', 8, 2)->nullable()->after('load_capacity'); // คิว
            $table->unsignedInteger('engine_cc')->nullable()->after('cubic_capacity');
            $table->unsignedSmallInteger('horsepower')->nullable()->after('engine_cc');
            $table->string('fuel_type', 30)->default('ดีเซล')->after('horsepower');
            $table->decimal('fuel_rate', 5, 2)->nullable()->after('fuel_type');      // กม./ลิตร
            $table->decimal('curb_weight', 8, 2)->nullable()->after('fuel_rate');    // น้ำหนักรถเปล่า (ตัน)

            $table->boolean('is_active')->default(true)->after('curb_weight');
        });
    }

    public function down(): void
    {
        Schema::table('truck_models', function (Blueprint $table) {
            $table->dropColumn([
                'model_year', 'truck_type', 'wheels', 'load_capacity',
                'cubic_capacity', 'engine_cc', 'horsepower', 'fuel_type',
                'fuel_rate', 'curb_weight', 'is_active',
            ]);
        });
    }
};