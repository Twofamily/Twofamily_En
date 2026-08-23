<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->decimal('cubic_capacity', 8, 2)->nullable()->after('fuelfactory_truck');
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn('cubic_capacity');
        });
    }
};
