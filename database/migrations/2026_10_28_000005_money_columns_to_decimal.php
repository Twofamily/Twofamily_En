<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คอลัมน์เงิน / จำนวน ที่ยังเป็นจำนวนเต็ม → decimal(12,2)
 *
 *   products.unit_price                         ราคา 390.50 ถูกตัดเหลือ 390
 *   quotation_details.quantity / price / total  หน้าใบเสนอราคากรอก 2.5 คิวได้ แต่ฐานข้อมูลเก็บเป็น 3 → ยอดเพี้ยน
 *   invoices / invoice_details / payments / receipts ที่ยังเป็น (10,2) → (12,2) ให้เท่ากับเอกสารอื่น
 *
 * ค่าเดิมเป็นจำนวนเต็มอยู่แล้ว แปลงเป็น decimal ไม่มีข้อมูลหาย
 */
return new class extends Migration
{
    private const UP = [
        'products'          => ['unit_price' => 0],
        'quotation_details' => ['quantity' => null, 'price_per_unit' => null, 'total_price' => null],
        'invoices'          => ['total' => 0, 'discount' => 0],
        'invoice_details'   => ['price' => null, 'total' => null],
        'payments'          => ['amount' => null],
        'receipts'          => ['total' => null],
    ];

    public function up(): void
    {
        foreach (self::UP as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column => $default) {
                    $col = $t->decimal($column, 12, 2);
                    if ($default !== null) {
                        $col->default($default);
                    }
                    $col->change();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->integer('unit_price')->default(0)->change());

        Schema::table('quotation_details', function (Blueprint $t) {
            $t->integer('quantity')->change();
            $t->integer('price_per_unit')->change();
            $t->integer('total_price')->change();
        });

        Schema::table('invoices', function (Blueprint $t) {
            $t->decimal('total', 10, 2)->default(0)->change();
            $t->decimal('discount', 10, 2)->default(0)->change();
        });
        Schema::table('invoice_details', function (Blueprint $t) {
            $t->decimal('price', 10, 2)->change();
            $t->decimal('total', 10, 2)->change();
        });
        Schema::table('payments', fn (Blueprint $t) => $t->decimal('amount', 10, 2)->change());
        Schema::table('receipts', fn (Blueprint $t) => $t->decimal('total', 10, 2)->change());
    }
};
