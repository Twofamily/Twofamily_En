<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_maintenances', function (Blueprint $table) {
            $table->bigIncrements('id_maintenance');
            $table->string('id_truck', 45);
            $table->string('title');                       // ซ่อมอะไร
            $table->text('detail')->nullable();            // รายละเอียดเพิ่มเติม
            $table->string('garage')->nullable();          // อู่ / ผู้ซ่อม
            $table->decimal('cost', 10, 2)->nullable();    // ค่าซ่อม
            $table->date('start_date');                    // วันที่เริ่มซ่อม
            $table->date('expected_return')->nullable();   // คาดว่าจะเสร็จ
            $table->date('finished_date')->nullable();     // เสร็จจริงเมื่อไหร่
            $table->timestamps();

            $table->foreign('id_truck')
                  ->references('id_truck')->on('trucks')
                  ->onDelete('cascade');

            $table->index(['id_truck', 'finished_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_maintenances');
    }
};
