<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->bigIncrements('id_delivery_note');
            $table->string('code_dn', 20)->nullable()->unique();   // DN-2569-0001
            $table->unsignedBigInteger('id_quotation')->nullable();
            $table->unsignedBigInteger('id_camp')->nullable();
            $table->unsignedInteger('id_customer');
            $table->date('delivery_date');
            $table->string('status')->default('delivered');
            $table->timestamps();

            $table->foreign('id_quotation')
                  ->references('id_quot')->on('quotations')
                  ->nullOnDelete();

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->nullOnDelete();

            $table->index('id_quotation');
            $table->index('id_camp');
        });

        Schema::create('delivery_note_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_delivery_note');
            $table->unsignedBigInteger('id_product');
            $table->decimal('quantity', 12, 2);
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->foreign('id_delivery_note')
                  ->references('id_delivery_note')->on('delivery_notes')
                  ->onDelete('cascade');

            $table->foreign('id_product')
                  ->references('id_product')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_details');
        Schema::dropIfExists('delivery_notes');
    }
};
