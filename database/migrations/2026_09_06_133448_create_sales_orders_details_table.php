<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_details', function (Blueprint $table) {

            $table->bigIncrements('id_sod');

            $table->unsignedBigInteger('id_so');
            $table->unsignedBigInteger('id_product');

            // จำนวนตามสัญญา = ตัวตั้งต้นของใบส่งของ
            $table->decimal('quantity', 12, 2);
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('total_price', 12, 2);

            $table->timestamps();

            $table->foreign('id_so')
                  ->references('id_so')->on('sales_orders')
                  ->onDelete('cascade');

            $table->foreign('id_product')
                  ->references('id_product')->on('products')
                  ->onDelete('restrict');

            $table->index(['id_so', 'id_product']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_details');
    }
};