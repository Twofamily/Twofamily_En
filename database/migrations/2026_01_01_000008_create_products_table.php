<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id_product');
            $table->string('name_product', 45);
            $table->string('detail_product', 255)->nullable();
            $table->integer('unit_price')->default(0);
            $table->timestamps(2);
            $table->softDeletes();

            $table->foreignId('product_type_id')
                  ->nullable()
                  ->constrained(table: 'product_types', column: 'id_product_type')
                  ->nullOnDelete();

            $table->string('image')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
