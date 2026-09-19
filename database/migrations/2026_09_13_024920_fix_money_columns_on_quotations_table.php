<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            // integer -> decimal เพื่อไม่ให้สตางค์หาย
            $table->decimal('subtotal', 12, 2)->default(0)->change();
            $table->decimal('discount', 12, 2)->default(0)->change();
            $table->decimal('total_amount', 12, 2)->default(0)->change();

            // เก็บอัตรา VAT ที่ใช้จริง ณ วันที่ออกเอกสาร
            // ถ้าอนาคตรัฐเปลี่ยนอัตรา เอกสารเก่ายังพิมพ์ถูกต้อง
            $table->decimal('vat_rate', 5, 2)->default(7.00)->after('discount');
            $table->decimal('vat_amount', 12, 2)->default(0)->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_amount']);

            $table->integer('subtotal')->default(0)->change();
            $table->integer('discount')->default(0)->change();
            $table->integer('total_amount')->default(0)->change();
        });
    }
};