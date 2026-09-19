<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $table      = 'sales_orders';
    protected $primaryKey = 'id_so';

    protected $fillable = [
        'code_so',
        'id_quot',
        'id_customer',
        'order_date',
        'due_date',
        'po_number',
        'status',
        'subtotal',
        'discount',
        'vat_amount',
        'total_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'order_date'   => 'date',
            'due_date'     => 'date',
            'subtotal'     => 'decimal:2',
            'discount'     => 'decimal:2',
            'vat_amount'   => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public const STATUS_LABELS = [
        'draft'       => 'ร่าง',
        'confirmed'   => 'ยืนยันแล้ว',
        'in_progress' => 'เปิดแคมป์แล้ว',
        'closed'      => 'ปิดงาน',
        'cancelled'   => 'ยกเลิก',
    ];

    public const STATUS_COLORS = [
        'draft'       => 'secondary',
        'confirmed'   => 'primary',
        'in_progress' => 'info',
        'closed'      => 'success',
        'cancelled'   => 'danger',
    ];

    public function getRouteKeyName()
    {
        return 'id_so';
    }

    /* ==================== Relations ==================== */

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    /** ใบสั่งขายนี้มาจากใบเสนอราคาใบไหน */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quot', 'id_quot');
    }

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'id_so', 'id_so');
    }

    /** 1 ใบสั่งขาย → 1 แคมป์ (งานต่องาน) */
    public function camp()
    {
        return $this->hasOne(Camp::class, 'id_so', 'id_so');
    }

    /** ใบส่งของทั้งหมดที่เกิดจากใบสั่งขายนี้ (ผ่านแคมป์) */
    public function deliveryNotes()
    {
        return $this->hasManyThrough(
            DeliveryNote::class,
            Camp::class,
            'id_so',      // FK บน camps
            'id_camp',    // FK บน delivery_notes
            'id_so',      // PK บน sales_orders
            'id_camp'     // PK บน camps
        );
    }

    /* ==================== Accessors ==================== */

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'ไม่ทราบสถานะ';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    /* ==================== Business rules ==================== */

    /** แก้ไขรายการได้เฉพาะตอนยังเป็นร่าง */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /** ยืนยันได้เมื่อเป็นร่างและมีรายการอย่างน้อย 1 */
    public function canConfirm(): bool
    {
        return $this->status === 'draft' && $this->details()->exists();
    }

    /** เปิดแคมป์ได้เมื่อยืนยันแล้ว และยังไม่เคยเปิด */
    public function canOpenCamp(): bool
    {
        return $this->status === 'confirmed' && ! $this->hasCamp();
    }

    public function hasCamp(): bool
    {
        return $this->camp()->exists();
    }

    /** ยกเลิกได้ตราบใดที่ยังไม่เปิดแคมป์ */
    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'confirmed'], true)
            && ! $this->hasCamp();
    }

    /* ==================== Scopes ==================== */

    /** ใบสั่งขายที่พร้อมเปิดแคมป์ = ยืนยันแล้ว + ยังไม่มีแคมป์ */
    public function scopeOpenableForCamp($query)
    {
        return $query->where('status', 'confirmed')
                     ->whereDoesntHave('camp');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    /* ==================== Helpers ==================== */

    public static function generateCode(): string
    {
        $prefix = 'SO-' . (now()->year + 543) . '-';

        $last = static::withTrashed()
            ->where('code_so', 'like', $prefix . '%')
            ->orderByDesc('code_so')
            ->value('code_so');

        $running = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($running, 4, '0', STR_PAD_LEFT);
    }
}