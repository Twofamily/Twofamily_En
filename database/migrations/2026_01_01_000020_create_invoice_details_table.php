<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_invoice');
            $table->unsignedBigInteger('id_product');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('id_invoice')
                  ->references('id_invoice')->on('invoices')
                  ->onDelete('cascade');

            $table->foreign('id_product')
                  ->references('id_product')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
