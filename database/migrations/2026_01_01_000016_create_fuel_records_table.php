<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_records', function (Blueprint $table) {
            $table->id('id_fuel_record');
            $table->date('date_record');
            $table->string('start_point', 255);
            $table->text('start_detail')->nullable();
            $table->string('destination', 255);
            $table->text('destination_detail')->nullable();
            $table->integer('age_truck')->nullable();
            $table->integer('depreciation')->nullable();
            $table->decimal('current_weight', 10, 2)->nullable();
            $table->decimal('max_load', 10, 2)->nullable();
            $table->decimal('distance', 10, 2)->nullable();
            $table->decimal('cost_fuel', 10, 2)->nullable();
            $table->decimal('cost_fuel_total', 10, 2)->nullable();
            $table->decimal('total_fuel_liters', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->string('trucks_id_truck', 45);
            $table->foreign('trucks_id_truck')
                  ->references('id_truck')->on('trucks')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_records');
    }
};
