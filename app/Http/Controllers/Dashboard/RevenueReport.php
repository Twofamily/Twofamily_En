<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * รวมตัวเลขรายได้และต้นทุนสำหรับแดชบอร์ด — อ่านจากฐานข้อมูลอย่างเดียว ไม่มีค่าสมมติ
 *
 * รายได้ใช้ delivery_notes + details (มูลค่างานที่ส่งจริง ก่อน VAT)
 * ไม่ใช้ invoices เพราะ invoices.total รวม VAT 7% ซึ่งไม่ใช่รายได้ของบริษัท
 *
 * แยกเป็น 2 สายตาม delivery_notes.id_camp:
 *   - ขายสินค้า     : ไม่ผูกแคมป์ (ส่งตรงให้ลูกค้า)
 *   - บริการแคมป์   : ผูกแคมป์
 *
 * ฝั่งต้นทุน รวมจาก 2 ทางที่ฐานข้อมูลมีช่องเก็บอยู่แล้ว:
 *   - ค่าน้ำมัน  : fuel_records.cost_fuel_total   → ใช้ตัวแม่เท่านั้น
 *                  (fuel_record_segments.fuel_cost คือตัวย่อยที่ถูกรวมมาแล้ว ถ้าบวกด้วยจะนับซ้ำ)
 *   - ค่าซ่อมรถ  : truck_maintenances.cost
 *
 * ทั้งสองเส้นไม่รวม VAT จึงเทียบกันได้ตรง
 *
 * ข้อจำกัด: ต้นทุนนี้ยังไม่รวมราคาทุนสินค้า เพราะ products ไม่มีคอลัมน์ราคาทุน
 * จึงยังคำนวณกำไรที่แท้จริงไม่ได้ (ดู DASHBOARD.md)
 */
class RevenueReport
{
    /** preset ช่วงเวลาที่เลือกได้ พร้อมชื่อไทยที่ใช้แสดงบนปุ่ม */
    public const PRESETS = [
        'this_month' => 'เดือนนี้',
        'last_month' => 'เดือนที่แล้ว',
        'this_year' => 'ปีนี้',
        'last_year' => 'ปีที่แล้ว',
        'last_12m' => '12 เดือนล่าสุด',
        'custom' => 'กำหนดเอง',
    ];

    public const BUCKETS = [
        'month' => 'รายเดือน',
        'year' => 'รายปี',
    ];

    /** ตัวเลือกบนหน้าจอ — auto ให้ระบบเดาความถี่จากความยาวช่วงที่เลือก */
    public const BUCKET_CHOICES = ['auto' => 'อัตโนมัติ'] + self::BUCKETS;

    private const MONTHS_TH = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    private string $preset;

    private string $bucket;

    private string $bucketChoice;

    public function __construct(Request $request)
    {
        [$this->preset, $this->from, $this->to] = $this->resolveRange($request);

        // เก็บสิ่งที่ผู้ใช้เลือกแยกจากผลลัพธ์จริง เพื่อให้ dropdown ค้างที่ "อัตโนมัติ" ได้
        $choice = (string) $request->query('bucket', 'auto');
        $this->bucketChoice = array_key_exists($choice, self::BUCKET_CHOICES) ? $choice : 'auto';
        $this->bucket = $this->resolveBucket();
    }

    /** ข้อมูลทั้งหมดที่ view ต้องใช้ */
    public function toArray(): array
    {
        $series = $this->series();

        return [
            'filter' => [
                'preset' => $this->preset,
                'bucket' => $this->bucket,
                'bucketChoice' => $this->bucketChoice,
                'bucketLabel' => self::BUCKETS[$this->bucket],
                'from' => $this->from->toDateString(),
                'to' => $this->to->toDateString(),
                'label' => $this->rangeLabel(),
                'presets' => self::PRESETS,
                'buckets' => self::BUCKET_CHOICES,
            ],
            'series' => $series,
            'hasData' => $series['totalRevenue'] > 0 || $series['totalCost'] > 0,
        ];
    }

    // ================= ช่วงเวลา =================

    /** @return array{0:string,1:CarbonImmutable,2:CarbonImmutable} */
    private function resolveRange(Request $request): array
    {
        $preset = (string) $request->query('preset', 'last_12m');
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = 'last_12m';
        }

        $today = CarbonImmutable::today();

        if ($preset === 'custom') {
            $from = $this->parseDate($request->query('from')) ?? $today->subMonths(11)->startOfMonth();
            $to = $this->parseDate($request->query('to')) ?? $today;

            // กรอกกลับหัวกลับหางก็ยังใช้ได้ สลับให้เอง
            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }

            return [$preset, $from->startOfDay(), $to->endOfDay()];
        }

        [$from, $to] = match ($preset) {
            'this_month' => [$today->startOfMonth(), $today->endOfMonth()],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->startOfYear(), $today->endOfYear()],
            'last_year' => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear()],
            default => [$today->subMonths(11)->startOfMonth(), $today->endOfMonth()],
        };

        return [$preset, $from->startOfDay(), $to->endOfDay()];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** ถ้าเลือก "อัตโนมัติ" ให้เดาจากความยาวช่วง: ไม่เกิน 3 ปีดูรายเดือน เกินนั้นดูรายปี */
    private function resolveBucket(): string
    {
        if ($this->bucketChoice !== 'auto') {
            return $this->bucketChoice;
        }

        return $this->from->diffInDays($this->to) <= 1100 ? 'month' : 'year';
    }

    private function rangeLabel(): string
    {
        return $this->thaiDate($this->from).' – '.$this->thaiDate($this->to);
    }

    private function thaiDate(CarbonImmutable $d): string
    {
        return $d->day.' '.self::MONTHS_TH[$d->month].' '.$d->year;
    }

    // ================= กราฟเส้นเวลา =================

    private function series(): array
    {
        $keys = $this->bucketKeys();
        $labels = array_map(fn ($k) => $this->bucketLabel($k), $keys);

        $campRevenue = $this->align($keys, $this->deliveredByBucket(withCamp: true));
        $productRevenue = $this->align($keys, $this->deliveredByBucket(withCamp: false));

        $fuel = $this->align($keys, $this->sumByBucket(
            DB::table('fuel_records')
                ->whereNull('deleted_at')
                ->whereBetween('date_record', [$this->from->toDateString(), $this->to->toDateString()]),
            'date_record',
            'cost_fuel_total'
        ));

        $maintenance = $this->align($keys, $this->sumByBucket(
            DB::table('truck_maintenances')
                ->whereBetween('start_date', [$this->from->toDateString(), $this->to->toDateString()]),
            'start_date',
            'cost'
        ));

        $split = $this->splitCost($productRevenue, $campRevenue, $fuel, $maintenance);

        return [
            'labels' => $labels,
            'product' => ['revenue' => $productRevenue] + $split['product'],
            'camp' => ['revenue' => $campRevenue] + $split['camp'],
            'totalRevenue' => array_sum($productRevenue) + array_sum($campRevenue),
            'totalCost' => array_sum($fuel) + array_sum($maintenance),
        ];
    }

    /**
     * รายได้ต่อ bucket แยกตามว่าใบส่งของผูกกับแคมป์หรือไม่
     *
     * delivery_notes.id_camp เป็นช่องเดียวในฐานข้อมูลที่บอกความต่างนี้ได้
     * มีแคมป์ = งานบริการแคมป์ · ไม่มีแคมป์ = ขายสินค้าส่งตรงให้ลูกค้า
     */
    private function deliveredByBucket(bool $withCamp): array
    {
        $query = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereBetween('dn.delivery_date', [$this->from->toDateString(), $this->to->toDateString()]);

        $withCamp
            ? $query->whereNotNull('dn.id_camp')
            : $query->whereNull('dn.id_camp');

        return $this->sumByBucket($query, 'dn.delivery_date', 'dnd.total_price');
    }

    /**
     * ปันส่วนต้นทุนเข้าสองสายตามสัดส่วนรายได้ของแต่ละช่วงเวลา
     *
     * ค่าน้ำมันกับค่าซ่อมบันทึกไว้ที่ "รถ" ไม่ได้ผูกกับใบส่งของหรือแคมป์
     * จึงแยกตามจริงไม่ได้ ตัวเลขที่ได้เป็นการประมาณ ไม่ใช่ต้นทุนที่วัดจริง
     * (จะแยกจริงได้ต้องเพิ่ม id_camp ให้ fuel_records / truck_maintenances — ดู DASHBOARD.md)
     *
     * ถ้าช่วงไหนไม่มีรายได้เลยแต่มีต้นทุน จะใช้สัดส่วนรายได้ของทั้งช่วงที่เลือกแทน
     * และถ้าทั้งช่วงก็ไม่มีรายได้ จึงค่อยหารครึ่ง — เพื่อไม่ให้ต้นทุนหายไปจากกราฟ
     */
    private function splitCost(array $product, array $camp, array $fuel, array $maintenance): array
    {
        $periodProduct = array_sum($product);
        $periodTotal = $periodProduct + array_sum($camp);
        $fallbackShare = $periodTotal > 0 ? $periodProduct / $periodTotal : 0.5;

        $out = [
            'product' => ['fuel' => [], 'maintenance' => [], 'cost' => []],
            'camp' => ['fuel' => [], 'maintenance' => [], 'cost' => []],
        ];

        foreach (array_keys($fuel) as $i) {
            $bucketTotal = $product[$i] + $camp[$i];
            $share = $bucketTotal > 0 ? $product[$i] / $bucketTotal : $fallbackShare;

            foreach (['fuel' => $fuel[$i], 'maintenance' => $maintenance[$i]] as $name => $amount) {
                $toProduct = round($amount * $share, 2);
                $out['product'][$name][$i] = $toProduct;
                // ที่เหลือยกให้แคมป์ทั้งหมด ผลรวมสองสายจึงเท่ากับต้นทุนจริงเสมอ ไม่หล่นเพราะปัดเศษ
                $out['camp'][$name][$i] = round($amount - $toProduct, 2);
            }

            $out['product']['cost'][$i] = round($out['product']['fuel'][$i] + $out['product']['maintenance'][$i], 2);
            $out['camp']['cost'][$i] = round($out['camp']['fuel'][$i] + $out['camp']['maintenance'][$i], 2);
        }

        return $out;
    }

    /** @return array<string,float> key = รหัส bucket เช่น 2026-09 */
    private function sumByBucket($query, string $dateCol, string $valueCol): array
    {
        $expr = $this->bucketExpr($dateCol);

        return $query
            ->selectRaw("$expr as bucket_key, SUM($valueCol) as amount")
            ->groupBy('bucket_key')
            ->pluck('amount', 'bucket_key')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function bucketExpr(string $col): string
    {
        return $this->bucket === 'year'
            ? "DATE_FORMAT($col, '%Y')"
            : "DATE_FORMAT($col, '%Y-%m')";
    }

    /** รายการ bucket ทั้งหมดในช่วง รวมช่องที่ไม่มีข้อมูล เพื่อให้กราฟไม่ขาดช่วง */
    private function bucketKeys(): array
    {
        $keys = [];
        $byYear = $this->bucket === 'year';
        $cursor = $byYear ? $this->from->startOfYear() : $this->from->startOfMonth();

        // กันลูปยาวเกินไปถ้ามีใครยิง custom range ข้ามร้อยปี
        $guard = 0;
        while ($cursor->lessThanOrEqualTo($this->to) && $guard++ < 1200) {
            $keys[] = $cursor->format($byYear ? 'Y' : 'Y-m');
            $cursor = $cursor->add($byYear ? '1 year' : '1 month');
        }

        return $keys;
    }

    private function bucketLabel(string $key): string
    {
        if ($this->bucket === 'year') {
            return $key;
        }

        [$year, $month] = explode('-', $key);

        return self::MONTHS_TH[(int) $month].' '.substr($year, 2);
    }

    private function align(array $keys, array $map): array
    {
        return array_map(fn ($k) => round($map[$k] ?? 0, 2), $keys);
    }
}
