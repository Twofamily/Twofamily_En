<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_records', function (Blueprint $table) {
            $table->decimal('distance', 10, 2)
                ->nullable()
                ->change();

            $table->decimal('current_weight', 10, 2)
                ->nullable()
                ->change();

            $table->decimal('max_load', 10, 2)
                ->nullable()
                ->change();

            $table->decimal('total_fuel_liters', 10, 2)
                ->nullable()
                ->after('cost_fuel_total');
        });

        Schema::create('fuel_record_segments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('fuel_record_id');

            $table->unsignedInteger('sequence');

            $table->string('start_point', 255);

            $table->string('destination', 255);

            $table->decimal('load_weight', 10, 2)
                ->default(0);

            $table->decimal('distance', 10, 2);

            $table->decimal('fuel_rate', 10, 2);

            $table->decimal('fuel_liters', 10, 2);

            $table->decimal('fuel_cost', 10, 2);

            $table->timestamps();

            $table->foreign('fuel_record_id')
                ->references('id_fuel_record')
                ->on('fuel_records')
                ->cascadeOnDelete();

            $table->unique([
                'fuel_record_id',
                'sequence',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_record_segments');

        Schema::table('fuel_records', function (Blueprint $table) {
            $table->dropColumn('total_fuel_liters');

            $table->integer('distance')
                ->nullable()
                ->change();

            $table->integer('current_weight')
                ->nullable()
                ->change();

            $table->integer('max_load')
                ->nullable()
                ->change();
        });
    }
};