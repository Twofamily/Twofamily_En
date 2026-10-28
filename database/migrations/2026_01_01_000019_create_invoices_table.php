<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id_invoice');
            $table->string('code_inv', 20)->nullable()->unique();  // INV-2569-0001
            $table->unsignedBigInteger('id_quotation');
            $table->unsignedInteger('id_customer');
            // แคมป์ของใบแจ้งหนี้: ใช้หักเงินรับล่วงหน้า และสรุปยอดรายแคมป์
            $table->unsignedBigInteger('id_camp')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->date('invoice_date');
            $table->timestamps();
            $table->unsignedBigInteger('id_delivery_note')->nullable()->unique();

            $table->foreign('id_quotation')
                  ->references('id_quot')->on('quotations')
                  ->onDelete('cascade');

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers');

            $table->foreign('id_camp')
                  ->references('id_camp')->on('camps')
                  ->nullOnDelete();

            $table->foreign('id_delivery_note')
                  ->references('id_delivery_note')->on('delivery_notes');

            $table->index('id_camp');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
