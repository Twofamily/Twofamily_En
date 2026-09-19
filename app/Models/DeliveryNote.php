<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasDocumentCode;

    // เลขที่เอกสารรูปแบบ DN-2569-0001 สร้างให้อัตโนมัติตอน create
    protected static string $codePrefix = 'DN';
    protected static string $codeColumn = 'code_dn';

    protected $primaryKey = 'id_delivery_note';

    protected $fillable = [
        'code_dn',
        'id_quotation',
        'id_camp',
        'id_customer',
        'delivery_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
        ];
    }

    /**
     * เดิมประกอบเลขจาก id (DN00002) ตอนนี้อ่านจากคอลัมน์ code_dn แทน
     * หน้าเว็บที่เรียก $dn->code_delivery อยู่แล้วจึงได้เลขรูปแบบใหม่ทันที ไม่ต้องไล่แก้
     */
    public function getCodeDeliveryAttribute(): string
    {
        return $this->attributes['code_dn'] ?? '';
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quotation', 'id_quot');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function details()
    {
        return $this->hasMany(DeliveryNoteDetail::class, 'id_delivery_note', 'id_delivery_note');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'id_delivery_note', 'id_delivery_note');
    }
}