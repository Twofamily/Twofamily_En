<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes, HasDocumentCode;

    // เลขที่เอกสารรูปแบบ QT-2569-0001 สร้างให้อัตโนมัติตอน create
    protected static string $codePrefix = 'QT';
    protected static string $codeColumn = 'code_quot';

    protected $primaryKey = 'id_quot';

    protected $fillable = [
        'code_quot',
        'id_customer',
        'date_quot',
        'end_quot',
        'status',
        'issued_by_user_id',
        'issued_at',
        'approved_by_user_id',
        'approved_at',
        'subtotal',
        'discount',
        'vat_rate',
        'vat_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'date_quot'    => 'date',
            'end_quot'     => 'date',
            'issued_at'    => 'datetime',
            'approved_at'  => 'datetime',
            'subtotal'     => 'decimal:2',
            'discount'     => 'decimal:2',
            'vat_rate'     => 'decimal:2',
            'vat_amount'   => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * อัตรา VAT มาตรฐาน ใช้เป็นค่าตั้งต้นตอนสร้างเอกสารใหม่
     * เอกสารแต่ละใบจะเก็บอัตราที่ใช้จริงลงคอลัมน์ vat_rate
     * ค่านี้จึงมีผลกับเอกสารใหม่เท่านั้น ไม่กระทบเอกสารเก่า
     */
    public const DEFAULT_VAT_RATE = 7.00;

    public const STATUS_LABELS = [
        'draft'    => 'ร่าง',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ยกเลิก',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'ไม่ทราบสถานะ';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /*
     * getCodeQuotAttribute() เดิมถูกลบออกแล้ว
     * code_quot เป็นคอลัมน์จริงในตาราง quotations
     * ถ้ายังมี accessor ชื่อเดียวกันอยู่ Laravel จะใช้ accessor แทนค่าในคอลัมน์
     * เลขใหม่จะไม่ถูกแสดง และ where('code_quot', ...) กับหน้าจอจะได้เลขคนละชุดกัน
     */

    /* ==================== Relations ==================== */

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function details()
    {
        return $this->hasMany(QuotationDetail::class, 'id_quot', 'id_quot');
    }

    /** ผู้จัดทำเอกสาร บันทึกไว้ ณ วันที่ออก */
    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id', 'id');
    }

    /** ผู้อนุมัติเอกสาร บันทึกไว้ ณ วันที่อนุมัติ */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id', 'id');
    }

    /** 1 ใบเสนอราคา → 1 ใบสั่งขาย */
    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'id_quot', 'id_quot');
    }

    /**
     * แคมป์ที่เปิดจากใบเสนอราคานี้ ผ่านใบสั่งขาย
     * เส้นทางใหม่: quotation → sales_order → camp
     */
    public function camp()
    {
        return $this->hasOneThrough(
            Camp::class,
            SalesOrder::class,
            'id_quot',   // FK บน sales_orders
            'id_so',     // FK บน camps
            'id_quot',   // PK บน quotations
            'id_so'      // PK บน sales_orders
        );
    }

    /**
     * @deprecated เส้นทางเก่า ผูกตรงกับ camps.id_quot
     * ยังเก็บไว้เพื่อไม่ให้แคมป์เดิมและโค้ดเดิมพัง
     * จะถูกถอดออกในเฟส 7 หลัง migrate ข้อมูลเสร็จ
     */
    public function camps()
    {
        return $this->hasMany(Camp::class, 'id_quot', 'id_quot');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_quotation', 'id_quot');
    }

    /* ==================== Money ==================== */

    /** ยอดหลังหักส่วนลด ก่อน VAT */
    public function getAfterDiscountAttribute(): float
    {
        return max((float) $this->subtotal - (float) $this->discount, 0);
    }

    /* ==================== Business rules ==================== */

    /** ออกใบสั่งขายได้เมื่ออนุมัติแล้ว และยังไม่เคยออก */
    public function canCreateSalesOrder(): bool
    {
        return $this->status === 'approved' && ! $this->hasSalesOrder();
    }

    public function hasSalesOrder(): bool
    {
        return $this->salesOrder()->exists();
    }

    /** ใบเสนอราคาหมดอายุแล้วหรือยัง */
    public function isExpired(): bool
    {
        return $this->end_quot && $this->end_quot->isPast();
    }

    /* ==================== Scopes ==================== */

    /** ใบเสนอราคาที่พร้อมออกใบสั่งขาย */
    public function scopeConvertible($query)
    {
        return $query->where('status', 'approved')
                     ->whereDoesntHave('salesOrder');
    }
}