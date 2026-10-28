<?php

namespace App\Models;

use App\Models\Concerns\Cancellable;
use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasDocumentCode, Cancellable;

    /** ยกเลิกเอกสาร = ใส่ cancelled_at (SoftDeletes) ไม่ลบแถวทิ้ง */
    public const DELETED_AT = 'cancelled_at';

    protected $primaryKey = 'id_delivery_note';

    protected $fillable = [
        'code_dn',
        'id_quotation',
        'id_camp',
        'id_customer',
        'id_invoice',
        'delivery_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'cancelled_at'  => 'datetime',
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

    /** ใบแจ้งหนี้ที่เรียกเก็บใบส่งของนี้ (ใบแจ้งหนี้ 1 ใบ รวมใบส่งของได้หลายใบ) */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice');
    }

    /** มูลค่าก่อน VAT ของใบส่งของนี้ */
    public function subtotal(): float
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->get();

        return round($details->sum(fn ($d) => (float) $d->total_price), 2);
    }

    /** ยกเลิกได้เมื่อยังไม่ได้ออกใบแจ้งหนี้ (ถ้าออกแล้ว ต้องยกเลิกใบแจ้งหนี้ก่อน) */
    public function cancelBlocker(): ?string
    {
        if ($this->isCancelled()) {
            return 'ใบส่งของนี้ถูกยกเลิกไปแล้ว';
        }
        if ($this->id_invoice && $this->invoice) {
            return 'ออกใบแจ้งหนี้ ' . $this->invoice->code_inv . ' แล้ว ต้องยกเลิกใบแจ้งหนี้ก่อน';
        }

        return null;
    }

    /** ใบส่งของที่ยังไม่ได้ออกใบแจ้งหนี้ */
    public function scopePendingInvoice($query)
    {
        return $query->whereNull('id_invoice');
    }
}