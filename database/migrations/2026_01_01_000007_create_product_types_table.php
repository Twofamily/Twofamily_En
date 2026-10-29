<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->bigIncrements('id_product_type');
            $table->string('name_product_type', 45);
            $table->timestamps(2);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
