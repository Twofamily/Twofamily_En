<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->bigIncrements('id_qd');
            $table->unsignedBigInteger('id_quot');
            $table->unsignedBigInteger('id_product');
            $table->integer('quantity');
            $table->integer('price_per_unit');
            $table->integer('total_price');
            $table->timestamps();

            $table->foreign('id_quot')
                  ->references('id_quot')->on('quotations')
                  ->onDelete('cascade');

            $table->foreign('id_product')
                  ->references('id_product')->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_details');
    }
};
