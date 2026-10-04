<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasDocumentCode;

    protected $primaryKey = 'id_receipt';

    protected $fillable = [
        'code_rc',
        'id_invoice',
        'id_customer',
        'total',
        'date_receipt',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }
}