<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_models', function (Blueprint $table) {
            $table->id();                                   // รหัสรุ่น
            $table->foreignId('truck_brand_id')->constrained('truck_brands')->onDelete('cascade');
            $table->string('name_model');                   // Victor 500, D-Max

            // ปีรุ่น - รุ่นเดียวกันคนละปี สเปคต่างกัน
            $table->year('model_year')->nullable();

            // ---- สเปคที่ดึงอัตโนมัติตอนบันทึกรถ ----
            $table->string('truck_type', 50)->nullable();          // ดัมพ์ / พ่วง / เทรลเลอร์
            $table->unsignedTinyInteger('wheels')->nullable();     // 6, 10, 12
            $table->decimal('load_capacity', 8, 2)->nullable();    // ตัน
            $table->decimal('cubic_capacity', 8, 2)->nullable();   // คิว
            $table->unsignedInteger('engine_cc')->nullable();
            $table->unsignedSmallInteger('horsepower')->nullable();
            $table->string('fuel_type', 30)->default('ดีเซล');
            $table->unsignedSmallInteger('tank_capacity')->nullable(); // ความจุถังน้ำมัน
            $table->decimal('fuel_rate', 5, 2)->nullable();        // กม./ลิตร
            $table->decimal('curb_weight', 8, 2)->nullable();      // น้ำหนักรถเปล่า (ตัน)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_models');
    }
};
