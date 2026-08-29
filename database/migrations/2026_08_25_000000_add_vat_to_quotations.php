<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->change();
            $table->decimal('discount', 12, 2)->default(0)->change();
            $table->decimal('total_amount', 12, 2)->default(0)->change();
        });

        DB::table('quotations')->orderBy('id_quot')->each(function ($quotation) {
            $subtotal = DB::table('quotation_details')
                ->where('id_quot', $quotation->id_quot)
                ->sum('total_price');
            $afterDiscount = max((float) $subtotal - (float) $quotation->discount, 0);
            $totalAmount = round($afterDiscount + round($afterDiscount * 0.07, 2), 2);

            DB::table('quotations')
                ->where('id_quot', $quotation->id_quot)
                ->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $totalAmount,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->integer('subtotal')->default(0)->change();
            $table->integer('discount')->default(0)->change();
            $table->integer('total_amount')->default(0)->change();
        });
    }
};
