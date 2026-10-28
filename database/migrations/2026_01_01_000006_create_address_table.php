<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedInteger('id_customer');   // ต้องเป็น int ให้ตรงกับ customers
            $table->text('customer_address');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_customer')
                  ->references('id_customer')->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address');
    }
};
