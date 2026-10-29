<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    // เลขที่เอกสาร (code_so) สร้างให้อัตโนมัติตอน create ตามรูปแบบในหน้าตั้งค่า
    use SoftDeletes, HasDocumentCode, RecordsCreator;

    protected $table      = 'sales_orders';
    protected $primaryKey = 'id_so';

    protected $fillable = [
        'created_by_user_id',
        'code_so',
        'id_quot',
        'id_customer',
        'id_camp',
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
        'in_progress' => 'อยู่ในแคมป์งาน',
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

    /**
     * ใบสั่งขายนี้อยู่ในแคมป์ไหน
     * 1 แคมป์ มีใบสั่งขายได้หลายใบ (ลูกค้าสั่งเพิ่มกลางงาน = เปิดใบสั่งขายใหม่ในแคมป์เดิม)
     */
    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    /**
     * ใบส่งของของแคมป์ที่ใบสั่งขายนี้อยู่
     * หมายเหตุ: ถ้าแคมป์มีหลายใบสั่งขาย จะเห็นใบส่งของของทั้งแคมป์
     * เพราะใบส่งของอ้างอิงแคมป์ ไม่ได้อ้างอิงใบสั่งขายรายใบ
     */
    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_camp', 'id_camp');
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

    /** สั่งตรง = ไม่ได้ออกจากใบเสนอราคา (ลูกค้าประจำโทรสั่ง ฯลฯ) */
    public function isDirect(): bool
    {
        return $this->id_quot === null;
    }

    /**
     * เหตุผลที่ผู้ใช้คนนี้ยืนยันใบสั่งขายไม่ได้ (null = ยืนยันได้)
     *
     * ใบที่มาจากใบเสนอราคา ราคาผ่านการอนุมัติมาแล้ว → พนักงานยืนยันเองได้
     * ใบสั่งตรง ราคายังไม่มีใครอนุมัติ → ใช้กฎเดียวกับการอนุมัติใบเสนอราคา
     *   - ต้องเป็นผู้ดูแลระบบ
     *   - ผู้ดูแลที่สร้างใบเอง ต้องให้ผู้ดูแลคนอื่นยืนยัน (ยกเว้นมีผู้ดูแลอยู่คนเดียว)
     */
    public function confirmBlocker(?User $user): ?string
    {
        if ($this->status !== 'draft') {
            return 'ใบสั่งขายนี้ไม่อยู่ในสถานะร่าง';
        }

        if (! $this->details()->exists()) {
            return 'ไม่มีรายการสินค้า ไม่สามารถยืนยันได้';
        }

        if (! $this->isDirect()) {
            return null;
        }

        if (! $user || ! $user->isAdmin()) {
            return 'ใบสั่งตรง (ไม่มีใบเสนอราคา) ต้องให้ผู้ดูแลระบบตรวจราคาและยืนยัน';
        }

        $isOwnDocument = (int) $this->created_by_user_id === (int) $user->getKey();
        $otherAdmins   = User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();

        if ($isOwnDocument && $otherAdmins) {
            return 'คุณเป็นผู้สร้างใบสั่งตรงนี้ ต้องให้ผู้ดูแลระบบคนอื่นยืนยัน';
        }

        return null;
    }

    /** เข้าแคมป์ได้ (เปิดแคมป์ใหม่ หรือผูกแคมป์เดิม) เมื่อยืนยันแล้ว และยังไม่อยู่ในแคมป์ไหน */
    public function canOpenCamp(): bool
    {
        return $this->status === 'confirmed' && ! $this->hasCamp();
    }

    public function hasCamp(): bool
    {
        return $this->id_camp !== null;
    }

    /** ยกเลิกได้ตราบใดที่ยังไม่เข้าแคมป์ */
    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'confirmed'], true)
            && ! $this->hasCamp();
    }

    /* ==================== Scopes ==================== */

    /** ใบสั่งขายที่พร้อมเข้าแคมป์ = ยืนยันแล้ว + ยังไม่อยู่ในแคมป์ */
    public function scopeOpenableForCamp($query)
    {
        return $query->where('status', 'confirmed')
                     ->whereNull('id_camp');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }
}