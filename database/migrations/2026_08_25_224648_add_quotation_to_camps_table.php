<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            // quotations.id_quot เป็น bigIncrements → ต้องใช้ unsignedBigInteger
            $table->unsignedBigInteger('id_quot')->nullable()->after('id_customer');

            $table->foreign('id_quot')
                  ->references('id_quot')->on('quotations')
                  ->nullOnDelete();

            $table->index('id_quot');
        });
    }

    public function down(): void
    {
        Schema::table('camps', function (Blueprint $table) {
            $table->dropForeign(['id_quot']);
            $table->dropIndex(['id_quot']);
            $table->dropColumn('id_quot');
        });
    }
};