<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * รายการของใบเสร็จแบบรับเงินล่วงหน้า / ขายสด
 * (ใบเสร็จแบบชำระตามใบแจ้งหนี้ใช้รายการจาก invoice_details)
 */
class ReceiptItem extends Model
{
    protected $primaryKey = 'id_receipt_item';

    protected $fillable = [
        'id_receipt',
        'id_product',
        'description',
        'quantity',
        'price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price'    => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'id_receipt', 'id_receipt');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /** ชื่อรายการ: ข้อความที่พิมพ์เองมาก่อน ไม่มีก็ใช้ชื่อสินค้า */
    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: ($this->product->name_product ?? '-');
    }
}
