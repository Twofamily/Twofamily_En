<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id_customer');
            $table->string('name_customer', 255);
            $table->enum('customer_type', ['person', 'company'])->default('person');

            // ติดต่อ
            $table->string('phone_customer', 10)->nullable();
            $table->string('email_customer')->nullable();

            // ที่อยู่
            $table->string('address_detail', 255)->nullable();
            $table->string('subdistrict', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('zipcode', 5)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
