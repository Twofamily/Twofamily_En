<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_brands', function (Blueprint $table) {
            $table->id();                                   // รหัสยี่ห้อ
            $table->string('name_brand')->unique();         // HINO, ISUZU
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_brands');
    }
};
