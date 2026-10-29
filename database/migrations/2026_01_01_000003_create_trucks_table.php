<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->string('id_truck', 45)->primary();
            $table->foreignId('truck_brand_id')->constrained('truck_brands')->onDelete('cascade');
            $table->foreignId('truck_model_id')->nullable()->constrained('truck_models')->onDelete('set null');
            $table->unsignedSmallInteger('year_truck')->nullable();
            $table->unsignedInteger('weight_truck')->nullable();
            $table->unsignedInteger('fuelfactory_truck')->nullable();
            $table->decimal('cubic_capacity', 8, 2)->nullable();
            $table->string('province_truck', 100);
            $table->decimal('fuel_rate', 8, 2);
            $table->enum('status_truck', ['active', 'maintenance', 'retired'])->default('active');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
