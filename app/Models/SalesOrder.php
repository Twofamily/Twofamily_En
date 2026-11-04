<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentCode;
use App\Models\Concerns\RecordsCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quot', 'id_quot');
    }

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'id_so', 'id_so');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'id_camp', 'id_camp');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_camp', 'id_camp');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'ไม่ทราบสถานะ';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    public function canConfirm(): bool
    {
        return $this->status === 'draft' && $this->details()->exists();
    }

    public function isDirect(): bool
    {
        return $this->id_quot === null;
    }

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

    public function canOpenCamp(): bool
    {
        return $this->status === 'confirmed' && ! $this->hasCamp();
    }

    public function hasCamp(): bool
    {
        return $this->id_camp !== null;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'confirmed'], true)
            && ! $this->hasCamp();
    }

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