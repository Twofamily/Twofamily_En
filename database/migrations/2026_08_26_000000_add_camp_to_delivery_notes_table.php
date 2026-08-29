<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เติม s ให้ตรงกับชื่อตารางหลักที่สร้าง
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['id_quotation']);
            $table->dropUnique(['id_quotation']);

            $table->unsignedBigInteger('id_quotation')->nullable()->change();

            $table->foreign('id_quotation')
                ->references('id_quot')->on('quotations')
                ->nullOnDelete();

            $table->index('id_quotation');

            $table->unsignedBigInteger('id_camp')->nullable()->after('id_quotation');

            $table->foreign('id_camp')
                ->references('id_camp')->on('camps')
                ->nullOnDelete();

            $table->index('id_camp');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['id_camp']);
            $table->dropIndex(['id_camp']);
            $table->dropColumn('id_camp');
        });
    }
};
