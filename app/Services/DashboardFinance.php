<?php

namespace App\Services;

use App\Models\Camp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวเลขการเงินของแดชบอร์ด — อ่านจากคอลัมน์ที่บันทึกไว้แล้วเท่านั้น ไม่มีค่าสมมติ
 *
 * นิยามที่ใช้ทั้งหน้า (ตรงกับ RevenueReport ที่วาดกราฟ):
 *   รายได้   = มูลค่าใบส่งของ (delivery_note_details.total_price) ก่อน VAT ตามวันส่งของ
 *   ต้นทุน   = ค่าน้ำมัน (fuel_records.cost_fuel_total) + ค่าซ่อม (truck_maintenances.cost)
 *   กำไรขั้นต้น = รายได้ − ต้นทุน  (ยังไม่รวมค่าแรงคนขับ / ราคาทุนวัสดุ เพราะระบบยังไม่เก็บ)
 *
 * ตัวเลขที่เป็น "เงินจริง" (เงินเข้า ลูกหนี้) รวม VAT เพราะเป็นยอดที่ลูกค้าจ่ายจริง
 *
 * ตัวเลขแบ่งเป็น 2 แบบ
 *   - ตามช่วงที่เลือก : รายได้ ต้นทุน กำไร เงินเข้า กำไรรายแคมป์ ต้นทุนรายรถ
 *   - ณ วันนี้        : ลูกหนี้ค้างรับ อายุหนี้ งานในมือ
 */
class DashboardFinance
{
    /**
     * อายุหนี้นับจาก "วันครบกำหนดชำระ" (ไม่ใช่วันออกบิล) — ตรงกับที่ฝ่ายบัญชีใช้ตามหนี้จริง
     * ขอบบนของช่วง (วันที่เลยกำหนด) => ป้าย   · ค่า ≤ 0 = ยังไม่ถึงกำหนด
     */
    private const AGING_BUCKETS = [
        0           => 'ยังไม่ถึงกำหนด',
        30          => 'เกินกำหนด 1–30 วัน',
        60          => 'เกินกำหนด 31–60 วัน',
        PHP_INT_MAX => 'เกินกำหนดมากกว่า 60 วัน',
    ];

    private bool $fuelHasCamp;

    private bool $invoiceHasDue;

    /** ใบแจ้งหนี้ค้างชำระ — ใช้ซ้ำทั้งการ์ดและตารางอายุหนี้ ไม่ query สองรอบ */
    private $receivables = null;

    public function __construct(
        private CarbonImmutable $from,
        private CarbonImmutable $to,
    ) {
        // ยังไม่ได้รัน migration เพิ่ม id_camp ให้ fuel_records ก็ยังเปิดหน้าได้
        $this->fuelHasCamp = Schema::hasColumn('fuel_records', 'id_camp');
        $this->invoiceHasDue = Schema::hasColumn('invoices', 'due_date');
    }

    // ==================== การ์ดตัวเลขหลัก ====================

    public function kpis(): array
    {
        $current = $this->totals($this->from, $this->to);

        [$prevFrom, $prevTo] = $this->previousRange();
        $previous = $this->totals($prevFrom, $prevTo);

        $receivable = $this->receivableRows();

        return [
            'current'  => $current,
            'previous' => $previous,
            'prevLabel' => $prevFrom->format('d/m/Y') . ' – ' . $prevTo->format('d/m/Y'),
            'change'   => [
                'revenue' => $this->percentChange($current['revenue'], $previous['revenue']),
                'cost'    => $this->percentChange($current['cost'], $previous['cost']),
                'profit'  => $this->percentChange($current['profit'], $previous['profit']),
                'cashIn'  => $this->percentChange($current['cashIn'], $previous['cashIn']),
            ],
            'receivable' => [
                'amount' => round($receivable->sum('remaining'), 2),
                'count'  => $receivable->count(),
            ],
        ];
    }

    /** ยอดรวมของช่วงใดช่วงหนึ่ง */
    private function totals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $revenue = (float) DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.cancelled_at')   // ใบส่งของที่ยกเลิกไม่นับ
            ->whereBetween('dn.delivery_date', $range)
            ->sum('dnd.total_price');

        $fuel = (float) DB::table('fuel_records')
            ->whereNull('deleted_at')
            ->whereBetween('date_record', $range)
            ->sum('cost_fuel_total');

        $maintenance = (float) DB::table('truck_maintenances')
            ->whereBetween('start_date', $range)
            ->sum('cost');

        // เงินที่เข้าบริษัทจริง = หลักฐานการชำระทุกแบบ (ตามใบแจ้งหนี้ + ขายสด)
        $cashIn = (float) DB::table('payments')
            // หลักฐานของใบเสร็จขายสดที่ยกเลิกแล้ว ไม่นับเป็นเงินเข้า
            ->whereNotExists(fn ($q) => $q->from('receipts as rc')
                ->whereColumn('rc.id_receipt', 'payments.id_receipt')
                ->whereNotNull('rc.cancelled_at'))
            ->whereBetween('paid_at', $range)
            ->sum('amount');

        $cost = $fuel + $maintenance;
        $profit = $revenue - $cost;

        return [
            'revenue'     => round($revenue, 2),
            'fuel'        => round($fuel, 2),
            'maintenance' => round($maintenance, 2),
            'cost'        => round($cost, 2),
            'profit'      => round($profit, 2),
            'margin'      => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
            'cashIn'      => round($cashIn, 2),
        ];
    }

    /**
     * ช่วงก่อนหน้าสำหรับเทียบ
     *   - ช่วงที่เป็นเดือนเต็ม (เดือนนี้ / ปีนี้ / 12 เดือน) → ถอยไปเท่าจำนวนเดือนเดิม
     *   - ช่วงที่กำหนดเอง → ถอยไปเท่าจำนวนวันเดิม
     */
    private function previousRange(): array
    {
        $from = $this->from->startOfDay();
        $to = $this->to->startOfDay();

        $wholeMonths = $from->day === 1 && $to->isSameDay($to->endOfMonth());

        if ($wholeMonths) {
            $months = ($to->year - $from->year) * 12 + ($to->month - $from->month) + 1;
            $prevFrom = $from->subMonthsNoOverflow($months);

            return [$prevFrom, $prevFrom->addMonthsNoOverflow($months)->subDay()];
        }

        $days = (int) round($from->diffInDays($to)) + 1;
        $prevTo = $from->subDay();

        return [$prevTo->subDays($days - 1), $prevTo];
    }

    private function percentChange(float $now, float $before): ?float
    {
        if (abs($before) < 0.01) {
            return null;   // ช่วงก่อนเป็นศูนย์ เทียบเป็น % ไม่ได้
        }

        return round(($now - $before) / abs($before) * 100, 1);
    }

    // ==================== กำไรรายแคมป์ ====================

    /**
     * รายได้ − ค่าน้ำมัน ของแต่ละแคมป์ ในช่วงที่เลือก
     *
     * ค่าซ่อมรถไม่ปันเข้าแคมป์ เพราะรถคันหนึ่งวิ่งหลายแคมป์ ซ่อมครั้งหนึ่งไม่รู้ว่าเป็นของแคมป์ไหน
     * จึงแสดงแยกไว้ใต้ตาราง พร้อมค่าน้ำมันรอบที่ไม่ได้ระบุแคมป์
     */
    public function campProfit(): array
    {
        $range = $this->dateRange();

        $revenue = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.cancelled_at')   // ใบส่งของที่ยกเลิกไม่นับ
            ->whereNotNull('dn.id_camp')
            ->whereBetween('dn.delivery_date', $range)
            ->groupBy('dn.id_camp')
            ->selectRaw('dn.id_camp as id_camp, SUM(dnd.total_price) as amount')
            ->pluck('amount', 'id_camp');

        $fuel = collect();
        $unassignedFuel = (float) DB::table('fuel_records')
            ->whereNull('deleted_at')
            ->whereBetween('date_record', $range)
            ->when($this->fuelHasCamp, fn ($q) => $q->whereNull('id_camp'))
            ->sum('cost_fuel_total');

        if ($this->fuelHasCamp) {
            $fuel = DB::table('fuel_records')
                ->whereNull('deleted_at')
                ->whereNotNull('id_camp')
                ->whereBetween('date_record', $range)
                ->groupBy('id_camp')
                ->selectRaw('id_camp, SUM(cost_fuel_total) as amount')
                ->pluck('amount', 'id_camp');
        }

        $campIds = $revenue->keys()->merge($fuel->keys())->unique()->values();

        $camps = DB::table('camps as c')
            ->leftJoin('customers as cu', 'cu.id_customer', '=', 'c.id_customer')
            ->whereIn('c.id_camp', $campIds)
            ->get(['c.id_camp', 'c.code_camp', 'c.name_camp', 'c.deleted_at', 'cu.name_customer'])
            ->keyBy('id_camp');

        $rows = $campIds->map(function ($id) use ($revenue, $fuel, $camps) {
            $camp = $camps->get($id);
            $rev = round((float) ($revenue[$id] ?? 0), 2);
            $cost = round((float) ($fuel[$id] ?? 0), 2);
            $profit = round($rev - $cost, 2);

            return [
                'id_camp'  => (int) $id,
                'code'     => $camp->code_camp ?? '-',
                'name'     => $camp->name_camp ?? 'แคมป์ที่ถูกลบ',
                'customer' => $camp->name_customer ?? '-',
                'deleted'  => $camp?->deleted_at !== null,
                'revenue'  => $rev,
                'fuel'     => $cost,
                'profit'   => $profit,
                'margin'   => $rev > 0 ? round($profit / $rev * 100, 1) : null,
            ];
        })
            // แคมป์ที่ขาดทุน / กำไรบางที่สุดขึ้นก่อน — ไม่มีรายได้เลยแต่มีค่าน้ำมัน = แย่สุด
            ->sortBy(fn ($r) => $r['margin'] ?? -INF)
            ->values();

        return [
            'rows'           => $rows,
            'totalRevenue'   => round($rows->sum('revenue'), 2),
            'totalFuel'      => round($rows->sum('fuel'), 2),
            'totalProfit'    => round($rows->sum('profit'), 2),
            'unassignedFuel' => round($unassignedFuel, 2),
            'maintenance'    => round((float) DB::table('truck_maintenances')
                ->whereBetween('start_date', $range)
                ->sum('cost'), 2),
            'fuelHasCamp'    => $this->fuelHasCamp,
        ];
    }

    // ==================== ลูกหนี้ ====================

    /**
     * ใบแจ้งหนี้ที่ยังมียอดค้าง (ณ วันนี้) พร้อมอายุหนี้
     * ยอดค้าง = ยอดใบแจ้งหนี้ − ทุกรายการชำระ
     * ตรงกับ Invoice::remainingAmount()
     */
    private function receivableRows()
    {
        if ($this->receivables !== null) {
            return $this->receivables;
        }

        $paid = DB::table('payments')
            ->whereNotNull('id_invoice')
            ->groupBy('id_invoice')
            ->select('id_invoice', DB::raw('SUM(amount) as paid'));

        return $this->receivables = DB::table('invoices as i')
            ->leftJoinSub($paid, 'p', 'p.id_invoice', '=', 'i.id_invoice')
            ->leftJoin('customers as cu', 'cu.id_customer', '=', 'i.id_customer')
            ->whereNull('i.cancelled_at')      // ใบแจ้งหนี้ที่ยกเลิกไม่ใช่หนี้
            ->whereRaw('i.total - COALESCE(p.paid, 0) > 0.005')
            ->get([
                'i.id_invoice',
                'i.id_customer',
                'cu.name_customer',
                DB::raw('ROUND(i.total - COALESCE(p.paid, 0), 2) as remaining'),
                // วันที่เลยกำหนด (ติดลบ = ยังไม่ถึงกำหนด) · ใบเก่าที่ไม่มีวันครบกำหนด ใช้วันที่ออกแทน
                DB::raw('DATEDIFF(CURDATE(), ' . ($this->invoiceHasDue
                    ? 'COALESCE(i.due_date, i.invoice_date)'
                    : 'i.invoice_date') . ') as age_days'),
            ])
            ->map(function ($r) {
                $r->remaining = (float) $r->remaining;
                $r->age_days = (int) $r->age_days;

                return $r;
            });
    }

    public function receivableAging(): array
    {
        $rows = $this->receivableRows();

        $buckets = [];
        $lower = PHP_INT_MIN;
        foreach (self::AGING_BUCKETS as $upper => $label) {
            $inBucket = $rows->filter(fn ($r) => $r->age_days >= $lower && $r->age_days <= $upper);
            $buckets[] = [
                'label'  => $label,
                'amount' => round($inBucket->sum('remaining'), 2),
                'count'  => $inBucket->count(),
                'late'   => $upper > 0,   // เลยกำหนดแล้ว → สีเตือน
            ];
            $lower = $upper + 1;
        }

        $total = round($rows->sum('remaining'), 2);

        $topCustomers = $rows->groupBy('id_customer')
            ->map(fn ($items) => [
                'name'     => $items->first()->name_customer ?? '-',
                'amount'   => round($items->sum('remaining'), 2),
                'invoices' => $items->count(),
                'oldest'   => max($items->max('age_days'), 0),   // เลยกำหนดนานสุดกี่วัน (0 = ยังไม่เลย)
            ])
            ->sortByDesc('amount')
            ->take(5)
            ->values();

        return compact('buckets', 'total', 'topCustomers');
    }

    // ==================== ต้นทุนรายรถ ====================

    /** ค่าน้ำมัน + ค่าซ่อม ของรถแต่ละคันในช่วงที่เลือก และค่าน้ำมันต่อกิโลเมตร */
    public function truckCosts(int $limit = 8): array
    {
        $range = $this->dateRange();

        $fuel = DB::table('fuel_records')
            ->whereNull('deleted_at')
            ->whereBetween('date_record', $range)
            ->groupBy('trucks_id_truck')
            ->get([
                'trucks_id_truck as id_truck',
                DB::raw('SUM(cost_fuel_total) as fuel'),
                DB::raw('SUM(distance) as distance'),
                DB::raw('COUNT(*) as trips'),
            ])
            ->keyBy('id_truck');

        $repair = DB::table('truck_maintenances')
            ->whereBetween('start_date', $range)
            ->groupBy('id_truck')
            ->selectRaw('id_truck, SUM(cost) as amount')
            ->pluck('amount', 'id_truck');

        $ids = $fuel->keys()->merge($repair->keys())->unique();

        $trucks = DB::table('trucks as t')
            ->leftJoin('truck_brands as b', 'b.id', '=', 't.truck_brand_id')
            ->whereIn('t.id_truck', $ids)
            ->get(['t.id_truck', 'b.name_brand'])
            ->keyBy('id_truck');

        $rows = $ids->map(function ($id) use ($fuel, $repair, $trucks) {
            $f = $fuel->get($id);
            $fuelCost = round((float) ($f->fuel ?? 0), 2);
            $distance = round((float) ($f->distance ?? 0), 2);
            $repairCost = round((float) ($repair[$id] ?? 0), 2);

            return [
                'id_truck'    => $id,
                'brand'       => $trucks->get($id)->name_brand ?? '',
                'trips'       => (int) ($f->trips ?? 0),
                'distance'    => $distance,
                'fuel'        => $fuelCost,
                'maintenance' => $repairCost,
                'total'       => round($fuelCost + $repairCost, 2),
                'perKm'       => $distance > 0 ? round($fuelCost / $distance, 2) : null,
            ];
        })->sortByDesc('total')->values();

        // ค่าเฉลี่ยของทั้งกอง ไว้เทียบว่าคันไหนกินน้ำมันเกินปกติ
        $distanceAll = $rows->sum('distance');
        $avgPerKm = $distanceAll > 0 ? round($rows->sum('fuel') / $distanceAll, 2) : null;

        return [
            'rows'     => $rows->take($limit),
            'more'     => max($rows->count() - $limit, 0),
            'avgPerKm' => $avgPerKm,
        ];
    }

    // ==================== งานในมือ ====================

    /**
     * มูลค่างานตามสัญญาที่ยังไม่ได้ส่ง (ณ วันนี้ ก่อน VAT)
     * = ยอดใบสั่งขายที่ยืนยันแล้วของแคมป์ − มูลค่าที่ส่งไปแล้วทั้งหมดของแคมป์
     * แคมป์เก่าที่ยังไม่มีใบสั่งขาย ใช้ยอดใบเสนอราคาแทน (เหมือน Camp::contractItems())
     */
    public function backlog(int $limit = 5): array
    {
        $contracts = DB::table('sales_orders')
            ->whereNull('deleted_at')
            ->whereNotNull('id_camp')
            ->whereIn('status', Camp::CONTRACT_SO_STATUSES)
            ->groupBy('id_camp')
            ->selectRaw('id_camp, SUM(subtotal - discount) as amount')
            ->pluck('amount', 'id_camp');

        $legacy = DB::table('camps as c')
            ->join('quotations as q', 'q.id_quot', '=', 'c.id_quot')
            ->whereNull('c.deleted_at')
            ->whereNotIn('c.id_camp', $contracts->keys())
            ->selectRaw('c.id_camp as id_camp, q.subtotal - q.discount as amount')
            ->pluck('amount', 'id_camp');

        $contracts = $contracts->union($legacy);

        $delivered = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.cancelled_at')   // ใบส่งของที่ยกเลิกไม่นับ
            ->whereIn('dn.id_camp', $contracts->keys())
            ->groupBy('dn.id_camp')
            ->selectRaw('dn.id_camp as id_camp, SUM(dnd.total_price) as amount')
            ->pluck('amount', 'id_camp');

        $camps = DB::table('camps')
            ->whereIn('id_camp', $contracts->keys())
            ->whereNull('deleted_at')
            ->where('status_camp', '!=', 'closed')   // แคมป์ที่ปิดแล้วไม่นับเป็นงานในมือ
            ->get(['id_camp', 'code_camp', 'name_camp'])
            ->keyBy('id_camp');

        $rows = $camps->map(function ($camp, $id) use ($contracts, $delivered) {
            $contract = round((float) $contracts[$id], 2);
            $done = round((float) ($delivered[$id] ?? 0), 2);

            return [
                'id_camp'   => (int) $id,
                'code'      => $camp->code_camp,
                'name'      => $camp->name_camp,
                'contract'  => $contract,
                'delivered' => $done,
                'remaining' => round(max($contract - $done, 0), 2),
                'percent'   => $contract > 0 ? min(round($done / $contract * 100), 100) : 0,
            ];
        })->filter(fn ($r) => $r['remaining'] > 0)->sortByDesc('remaining')->values();

        return [
            'total' => round($rows->sum('remaining'), 2),
            'camps' => $rows->count(),
            'rows'  => $rows->take($limit),
        ];
    }

    // ==================== helper ====================

    private function dateRange(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }
}
