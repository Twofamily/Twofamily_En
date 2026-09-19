<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasDocumentCode;

    // เลขที่เอกสารรูปแบบ INV-2569-0001 สร้างให้อัตโนมัติตอน create
    protected static string $codePrefix = 'INV';
    protected static string $codeColumn = 'code_inv';

    protected $primaryKey = 'id_invoice';

    protected $fillable = [
        'code_inv',
        'id_quotation',
        'id_delivery_note',
        'id_customer',
        'discount',
        'total',
        'status'
    ];

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'id_invoice');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quotation', 'id_quot');
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'id_delivery_note', 'id_delivery_note');
    }
}