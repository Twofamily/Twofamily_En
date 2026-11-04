<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Camp extends Model
{
    use SoftDeletes;

    protected $table      = 'camps';
    protected $primaryKey = 'id_camp';

    protected $fillable = [
        'id_customer',
        'id_so',
        'id_quot',
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

    public const WORKING_STATUSES = ['preparing', 'active'];

    public const NEXT_STEP = [
        'preparing' => ['active',    'เริ่มดำเนินงาน',        'bi-play-circle'],
        'active'    => ['completed', 'ส่งงานครบแล้ว',        'bi-check2-circle'],
        'completed' => ['closed',    'ปิดแคมป์',            'bi-lock'],
        'closed'    => ['active',    'เปิดแคมป์อีกครั้ง',      'bi-unlock'],
    ];

    public const CONTRACT_SO_STATUSES = ['confirmed', 'in_progress', 'closed'];

    public function getRouteKeyName()
    {
        return 'id_camp';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'id_camp', 'id_camp');
    }

    public function contractSalesOrders()
    {
        return $this->salesOrders()->whereIn('status', self::CONTRACT_SO_STATUSES);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'id_so', 'id_so');
    }

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

    public function trucks()
    {
        return $this->belongsToMany(Truck::class, 'camp_truck', 'id_camp', 'id_truck')
            ->using(CampTruck::class)
            ->withPivot('id_assignment', 'id_driver', 'assigned_date', 'released_date', 'note')
            ->withTimestamps();
    }

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

    public function hasContract(): bool
    {
        return $this->contractSalesOrders()->exists() || (bool) $this->id_quot;
    }

    public function getContractCodeAttribute(): ?string
    {
        $codes = $this->contractSalesOrders()->orderBy('id_so')->pluck('code_so');

        if ($codes->isNotEmpty()) {
            return $codes->implode(', ');
        }

        return $this->quotation?->code_quot;
    }

    public function contractValue(): float
    {
        $sum = (float) $this->contractSalesOrders()->sum('total_amount');

        if ($sum > 0) {
            return round($sum, 2);
        }

        return round((float) ($this->quotation->total_amount ?? 0), 2);
    }

    public function financialSummary(): array
    {
        $invoiceIds = $this->invoices()->pluck('id_invoice');

        $deliveredValue = (float) DeliveryNoteDetail::whereIn(
            'id_delivery_note',
            $this->deliveryNotes()->pluck('id_delivery_note')
        )->sum('total_price');

        $invoiced = (float) $this->invoices()->sum('total');

        $paidOnInvoices = (float) Payment::whereIn('id_invoice', $invoiceIds)->sum('amount');

        $directReceived = (float) $this->receipts()
            ->where('receipt_type', Receipt::TYPE_DIRECT)
            ->sum('total');

        return [
            'contract_value'   => $this->contractValue(),
            'delivered_value'  => round($deliveredValue, 2),
            'invoiced'         => round($invoiced, 2),
            'paid_on_invoices' => round($paidOnInvoices, 2),
            'direct_received'  => round($directReceived, 2),
            'cash_received'    => round($paidOnInvoices + $directReceived, 2),
            'outstanding'      => round(max($invoiced - $paidOnInvoices, 0), 2),
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status_camp', self::WORKING_STATUSES);
    }

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

    public function markStartedIfPreparing(): void
    {
        if ($this->status_camp === 'preparing') {
            $this->update(['status_camp' => 'active']);
        }
    }

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
