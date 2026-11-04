<?php

namespace App\Services;

use App\Models\Camp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardFinance
{
    private const AGING_BUCKETS = [
        0           => 'ยังไม่ถึงกำหนด',
        30          => 'เกินกำหนด 1–30 วัน',
        60          => 'เกินกำหนด 31–60 วัน',
        PHP_INT_MAX => 'เกินกำหนดมากกว่า 60 วัน',
    ];

    private bool $fuelHasCamp;

    private bool $invoiceHasDue;

    private $receivables = null;

    public function __construct(
        private CarbonImmutable $from,
        private CarbonImmutable $to,
    ) {
        $this->fuelHasCamp = Schema::hasColumn('fuel_records', 'id_camp');
        $this->invoiceHasDue = Schema::hasColumn('invoices', 'due_date');
    }

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

    private function totals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from->toDateString(), $to->toDateString()];

        $revenue = (float) DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.cancelled_at')
            ->whereBetween('dn.delivery_date', $range)
            ->sum('dnd.total_price');

        $fuel = (float) DB::table('fuel_records')
            ->whereNull('deleted_at')
            ->whereBetween('date_record', $range)
            ->sum('cost_fuel_total');

        $maintenance = (float) DB::table('truck_maintenances')
            ->whereBetween('start_date', $range)
            ->sum('cost');

        $cashIn = (float) DB::table('payments')
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
            return null;
        }

        return round(($now - $before) / abs($before) * 100, 1);
    }

    public function campProfit(): array
    {
        $range = $this->dateRange();

        $revenue = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.cancelled_at')
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
            ->whereNull('i.cancelled_at')
            ->whereRaw('i.total - COALESCE(p.paid, 0) > 0.005')
            ->get([
                'i.id_invoice',
                'i.id_customer',
                'cu.name_customer',
                DB::raw('ROUND(i.total - COALESCE(p.paid, 0), 2) as remaining'),
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
                'late'   => $upper > 0,
            ];
            $lower = $upper + 1;
        }

        $total = round($rows->sum('remaining'), 2);

        $topCustomers = $rows->groupBy('id_customer')
            ->map(fn ($items) => [
                'name'     => $items->first()->name_customer ?? '-',
                'amount'   => round($items->sum('remaining'), 2),
                'invoices' => $items->count(),
                'oldest'   => max($items->max('age_days'), 0),
            ])
            ->sortByDesc('amount')
            ->take(5)
            ->values();

        return compact('buckets', 'total', 'topCustomers');
    }

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

        $distanceAll = $rows->sum('distance');
        $avgPerKm = $distanceAll > 0 ? round($rows->sum('fuel') / $distanceAll, 2) : null;

        return [
            'rows'     => $rows->take($limit),
            'more'     => max($rows->count() - $limit, 0),
            'avgPerKm' => $avgPerKm,
        ];
    }

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
            ->whereNull('dn.cancelled_at')
            ->whereIn('dn.id_camp', $contracts->keys())
            ->groupBy('dn.id_camp')
            ->selectRaw('dn.id_camp as id_camp, SUM(dnd.total_price) as amount')
            ->pluck('amount', 'id_camp');

        $camps = DB::table('camps')
            ->whereIn('id_camp', $contracts->keys())
            ->whereNull('deleted_at')
            ->where('status_camp', '!=', 'closed')
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

    private function dateRange(): array
    {
        return [$this->from->toDateString(), $this->to->toDateString()];
    }
}
