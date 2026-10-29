<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * แคมป์งาน = แฟ้มงาน 1 งาน
 *
 * เก็บข้อมูลอ้างอิงของงาน: ลูกค้า สถานที่ วัสดุที่สั่ง (จากใบสั่งขาย) รถที่ทำงาน
 * เอกสารทุกใบ (ใบส่งของ ใบแจ้งหนี้ ใบเสร็จ) ชี้มาที่แคมป์ผ่าน id_camp
 * แต่ไม่บังคับว่าต้องออกเอกสารเรียงตามลำดับ
 *
 * 1 แคมป์ มีใบสั่งขายได้หลายใบ (sales_orders.id_camp)
 * ลูกค้าสั่งเพิ่มกลางงาน → เปิดใบสั่งขายใบใหม่ แล้วผูกเข้าแคมป์เดิม
 */
class Camp extends Model
{
    use SoftDeletes;

    protected $table      = 'camps';
    protected $primaryKey = 'id_camp';

    protected $fillable = [
        'id_customer',
        'id_so',       // @deprecated เส้นทางเดิม (1:1) — โค้ดใหม่ไม่เขียนคอลัมน์นี้แล้ว
        'id_quot',     // แคมป์เก่าที่ผูกใบเสนอราคาตรง ๆ
        'code_camp',
        'name_camp',
        'address_detail',
        'subdistrict',
        'district',
        'province',
        'zipcode',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'status_camp',
        'note',
    ];

    /**
     * วงจรของแคมป์
     *   preparing → active → completed → closed
     *   เตรียมงาน   ดำเนินงาน  ส่งครบ รอเก็บเงิน  ปิดแคมป์
     */
    public const STATUS_LABELS = [
        'preparing' => 'เตรียมงาน',
        'active'    => 'กำลังดำเนินงาน',
        'completed' => 'ส่งงานครบ รอเก็บเงิน',
        'closed'    => 'ปิดแคมป์แล้ว',
    ];

    public const STATUS_COLORS = [
        'preparing' => 'info',
        'active'    => 'success',
        'completed' => 'warning',
        'closed'    => 'secondary',
    ];

    /** สถานะที่ยังส่งของ / วิ่งรถ / บันทึกน้ำมันได้ */
    public const WORKING_STATUSES = ['preparing', 'active'];

    /** ปุ่มขั้นถัดไปบนหน้าแคมป์: สถานะปัจจุบัน => [สถานะถัดไป, ข้อความปุ่ม, ไอคอน] */
    public const NEXT_STEP = [
        'preparing' => ['active',    'เริ่มดำเนินงาน',        'bi-play-circle'],
        'active'    => ['completed', 'ส่งงานครบแล้ว',        'bi-check2-circle'],
        'completed' => ['closed',    'ปิดแคมป์',            'bi-lock'],
        'closed'    => ['active',    'เปิดแคมป์อีกครั้ง',      'bi-unlock'],
    ];

    /** สถานะใบสั่งขายที่นับเป็นยอดตามสัญญา (ไม่นับร่างและที่ยกเลิก) */
    public const CONTRACT_SO_STATUSES = ['confirmed', 'in_progress', 'closed'];

    public function getRouteKeyName()
    {
        return 'id_camp';
    }

    /* ==================== Relations ==================== */

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    /** ใบสั่งขายทั้งหมดในแคมป์นี้ */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'id_camp', 'id_camp');
    }

    /** ใบสั่งขายที่นับเป็นสัญญา */
    public function contractSalesOrders()
    {
        return $this->salesOrders()->whereIn('status', self::CONTRACT_SO_STATUSES);
    }

    /**
     * @deprecated เส้นทางเดิม camps.id_so (1 ใบสั่งขาย = 1 แคมป์)
     * ใช้ salesOrders() แทน — เก็บไว้ไม่ให้โค้ดเก่าพัง ถอดออกในเฟส 7
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'id_so', 'id_so');
    }

    /** ใบเสนอราคาต้นทาง (แคมป์เก่าที่ผูกใบเสนอราคาตรง ๆ) */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'id_quot', 'id_quot');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'id_camp', 'id_camp');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_camp', 'id_camp');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'id_camp', 'id_camp');
    }

    /** รถที่มอบหมายเข้าแคมป์ (pivot มีคนขับของรถคันนั้นด้วย: $truck->pivot->driver) */
    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'camp_truck', 'id_camp', 'id_truck')
            ->using(CampTruck::class)
            ->withPivot('id_assignment', 'id_driver', 'assigned_date', 'released_date', 'note')
            ->withTimestamps();
    }

    /** คนขับที่เคย/กำลังทำงานที่แคมป์นี้ */
    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'camp_truck', 'id_camp', 'id_driver')
            ->using(CampTruck::class)
            ->withPivot('id_assignment', 'id_truck', 'assigned_date', 'released_date', 'note')
            ->withTimestamps();
    }

    public function activeDrivers()
    {
        return $this->drivers()->wherePivotNull('released_date');
    }

    public function activeTrucks()
    {
        return $this->trucks()->wherePivotNull('released_date');
    }

    /* ==================== ยอดตามสัญญา ==================== */

    /**
     * รายการตามสัญญาของแคมป์นี้ (รวมทุกใบสั่งขาย แยกตามสินค้า)
     *
     * ใช้เป็นแหล่งเดียวสำหรับคำนวณ "ส่งไปแล้วเท่าไร เหลือเท่าไร"
     * ทั้งใน CampController::buildProgress() และ DeliveryNoteController
     *
     * ลำดับความสำคัญ:
     *   1. ใบสั่งขายในแคมป์ (ยืนยันแล้วขึ้นไป) — สินค้าเดียวกันหลายใบ รวมจำนวน
     *      ราคาต่อหน่วยใช้ของใบสั่งขายล่าสุด
     *   2. ใบเสนอราคา (แคมป์เก่าที่ยังไม่ได้ migrate)
     */
    public function contractItems()
    {
        $orders = $this->contractSalesOrders()
            ->with('details.product')
            ->orderBy('id_so')
            ->get();

        if ($orders->isNotEmpty()) {
            return $orders
                ->flatMap(fn ($so) => $so->details)
                ->groupBy('id_product')
                ->map(fn ($rows, $idProduct) => [
                    'id_product'     => (int) $idProduct,
                    'name_product'   => $rows->last()->product->name_product ?? '-',
                    'quantity'       => (float) $rows->sum(fn ($d) => (float) $d->quantity),
                    'price_per_unit' => (float) $rows->last()->price_per_unit,
                ])
                ->values();
        }

        if ($this->id_quot && $this->quotation) {
            return $this->quotation->details->map(fn ($detail) => [
                'id_product'     => $detail->id_product,
                'name_product'   => $detail->product->name_product ?? '-',
                'quantity'       => (float) $detail->quantity,
                'price_per_unit' => (float) $detail->price_per_unit,
            ])->values();
        }

        return collect();
    }

    /** แคมป์นี้มีสัญญาผูกอยู่หรือไม่ (ไม่ว่าทางไหน) */
    public function hasContract(): bool
    {
        return $this->contractSalesOrders()->exists() || (bool) $this->id_quot;
    }

    /** เลขเอกสารต้นทางไว้แสดงผล เช่น "SO-2569-0003, SO-2569-0007" */
    public function getContractCodeAttribute(): ?string
    {
        $codes = $this->contractSalesOrders()->orderBy('id_so')->pluck('code_so');

        if ($codes->isNotEmpty()) {
            return $codes->implode(', ');
        }

        return $this->quotation?->code_quot;
    }

    /** มูลค่าตามสัญญา (รวม VAT) */
    public function contractValue(): float
    {
        $sum = (float) $this->contractSalesOrders()->sum('total_amount');

        if ($sum > 0) {
            return round($sum, 2);
        }

        return round((float) ($this->quotation->total_amount ?? 0), 2);
    }

    /* ==================== สรุปการเงินของแคมป์ ==================== */

    /**
     * ตัวเลขสรุปของแคมป์ ใช้ในหน้าดูแคมป์
     * ทุกตัวอ่านจากคอลัมน์ที่บันทึกไว้แล้ว ไม่คำนวณราคาใหม่
     */
    public function financialSummary(): array
    {
        $invoiceIds = $this->invoices()->pluck('id_invoice');

        $deliveredValue = (float) DeliveryNoteDetail::whereIn(
            'id_delivery_note',
            $this->deliveryNotes()->pluck('id_delivery_note')
        )->sum('total_price');

        $invoiced = (float) $this->invoices()->sum('total');

        // เงินที่ลูกค้าจ่ายตามใบแจ้งหนี้
        $paidOnInvoices = (float) Payment::whereIn('id_invoice', $invoiceIds)->sum('amount');

        // ขายสดที่ระบุแคมป์ไว้
        $directReceived = (float) $this->receipts()
            ->where('receipt_type', Receipt::TYPE_DIRECT)
            ->sum('total');

        return [
            'contract_value'   => $this->contractValue(),
            'delivered_value'  => round($deliveredValue, 2),   // ก่อน VAT
            'invoiced'         => round($invoiced, 2),
            'paid_on_invoices' => round($paidOnInvoices, 2),
            'direct_received'  => round($directReceived, 2),
            'cash_received'    => round($paidOnInvoices + $directReceived, 2),
            'outstanding'      => round(max($invoiced - $paidOnInvoices, 0), 2),
        ];
    }

    /* ==================== Scopes & Accessors ==================== */

    /** แคมป์ที่ยังทำงานอยู่ (เตรียมงาน / กำลังดำเนินงาน) — ส่งของ วิ่งรถ บันทึกน้ำมันได้ */
    public function scopeActive($query)
    {
        return $query->whereIn('status_camp', self::WORKING_STATUSES);
    }

    /** แคมป์ที่ยังไม่ปิด — เพิ่มใบสั่งขาย / ออกใบแจ้งหนี้ได้ */
    public function scopeOpen($query)
    {
        return $query->where('status_camp', '!=', 'closed');
    }

    public function isWorking(): bool
    {
        return in_array($this->status_camp, self::WORKING_STATUSES, true);
    }

    public function isOpen(): bool
    {
        return $this->status_camp !== 'closed';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status_camp] ?? 'secondary';
    }

    /** ส่งของใบแรกแล้ว → แคมป์ที่ยังเตรียมงานอยู่ ถือว่าเริ่มงาน */
    public function markStartedIfPreparing(): void
    {
        if ($this->status_camp === 'preparing') {
            $this->update(['status_camp' => 'active']);
        }
    }

    /**
     * เหตุผลที่ลบแคมป์ไม่ได้ (ว่าง = ลบได้)
     *
     * แคมป์ที่มีเอกสารแล้วต้องเก็บไว้ ให้ใช้ "ปิดแคมป์" แทน
     * นับเอกสารที่ยกเลิกแล้วด้วย เพราะเอกสารเหล่านั้นยังอ้างถึงแคมป์นี้อยู่
     */
    public function deleteBlocker(): ?string
    {
        $reasons = [];

        if ($n = $this->deliveryNotes()->withTrashed()->count()) {
            $reasons[] = "ใบส่งของ {$n} ใบ";
        }
        if ($n = $this->invoices()->withTrashed()->count()) {
            $reasons[] = "ใบแจ้งหนี้ {$n} ใบ";
        }
        if ($n = $this->receipts()->withTrashed()->count()) {
            $reasons[] = "ใบเสร็จ {$n} ใบ";
        }
        if ($n = FuelRecord::where('id_camp', $this->id_camp)->count()) {
            $reasons[] = "บันทึกน้ำมัน {$n} รายการ";
        }
        if ($n = $this->activeTrucks()->count()) {
            $reasons[] = "รถประจำแคมป์ {$n} คัน";
        }

        return $reasons ? 'มี' . implode(' · ', $reasons) . ' — ใช้ "ปิดแคมป์" แทน' : null;
    }

    /**
     * เหตุผลที่ยังปิดแคมป์ไม่ได้ (ว่าง = ปิดได้)
     *
     * ปิดแคมป์ = จบงานทั้งหมดแล้ว จึงต้อง
     *   1. ใบส่งของทุกใบออกใบแจ้งหนี้แล้ว (ไม่มีงานที่ลืมเก็บเงิน)
     *   2. ไม่มียอดค้างชำระ
     *   3. ถอนรถออกจากแคมป์หมดแล้ว
     */
    public function closeBlockers(): array
    {
        $blockers = [];

        $pending = $this->deliveryNotes()->whereNull('id_invoice')->count();
        if ($pending > 0) {
            $blockers[] = "ใบส่งของ {$pending} ใบ ยังไม่ได้ออกใบแจ้งหนี้";
        }

        $summary = $this->financialSummary();

        if ($summary['outstanding'] > 0) {
            $blockers[] = 'ลูกค้ายังค้างชำระ ' . number_format($summary['outstanding'], 2) . ' บาท';
        }

        $trucks = $this->activeTrucks()->count();
        if ($trucks > 0) {
            $blockers[] = "ยังมีรถประจำแคมป์ {$trucks} คัน (ถอนรถออกก่อน)";
        }

        return $blockers;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status_camp] ?? '-';
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_detail,
            $this->subdistrict ? 'ต.' . $this->subdistrict : null,
            $this->district    ? 'อ.' . $this->district : null,
            $this->province    ? 'จ.' . $this->province : null,
            $this->zipcode,
        ])->filter()->implode(' ');
    }

    public static function generateCode(): string
    {
        $prefix = 'CP-' . (now()->year + 543) . '-';

        $last = static::withTrashed()
            ->where('code_camp', 'like', $prefix . '%')
            ->orderByDesc('code_camp')
            ->value('code_camp');

        $running = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($running, 4, '0', STR_PAD_LEFT);
    }
}
