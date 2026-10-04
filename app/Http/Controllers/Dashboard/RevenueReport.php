<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * รวมตัวเลขรายได้สำหรับแดชบอร์ด — อ่านจากฐานข้อมูลอย่างเดียว ไม่มีค่าสมมติ
 *
 * แหล่งข้อมูล 3 ทาง ซึ่งตอบคนละคำถาม:
 *   - มูลค่างานที่ส่ง : delivery_notes + details  → งานที่ทำจริง ผูกกับแคมป์ได้
 *   - ออกบิล          : invoices                  → ยอดที่เรียกเก็บแล้ว
 *   - เงินเข้าจริง     : payments                  → เงินที่รับเข้ามาแล้ว
 *
 * หมายเหตุต้นทุน: ตอนนี้ยังคำนวณกำไรไม่ได้ เพราะ products ไม่มีราคาทุน
 * และ fuel_records / truck_maintenances ยังไม่มีข้อมูล (ดู DASHBOARD.md)
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
        'day' => 'รายวัน',
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
            'kpi' => $this->kpi(),
            'series' => $series,
            'byCamp' => $this->byCamp(),
            'byProduct' => $this->byProduct(),
            'hasData' => array_sum($series['delivered']) > 0
                        || array_sum($series['invoiced']) > 0
                        || array_sum($series['paid']) > 0,
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

    /** ถ้าเลือก "อัตโนมัติ" ให้เดาจากความยาวช่วง: ช่วงสั้นดูรายวัน ช่วงยาวดูรายปี */
    private function resolveBucket(): string
    {
        if ($this->bucketChoice !== 'auto') {
            return $this->bucketChoice;
        }

        $days = $this->from->diffInDays($this->to);

        return match (true) {
            $days <= 62 => 'day',
            $days <= 1100 => 'month',
            default => 'year',
        };
    }

    private function rangeLabel(): string
    {
        return $this->thaiDate($this->from).' – '.$this->thaiDate($this->to);
    }

    private function thaiDate(CarbonImmutable $d): string
    {
        return $d->day.' '.self::MONTHS_TH[$d->month].' '.$d->year;
    }

    // ================= KPI =================

    private function kpi(): array
    {
        $delivered = (float) DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereBetween('dn.delivery_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->sum('dnd.total_price');

        // invoices ไม่มีคอลัมน์วันที่ออกบิล จึงต้องใช้ created_at — ถ้าออกบิลย้อนหลังตัวเลขจะเข้าเดือนที่บันทึก
        $invoiced = (float) DB::table('invoices')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->sum('total');

        $paid = (float) DB::table('payments')
            ->whereBetween('paid_at', [$this->from->toDateString(), $this->to->toDateString()])
            ->sum('amount');

        return [
            'delivered' => $delivered,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'outstanding' => $invoiced - $paid,
        ];
    }

    // ================= กราฟเส้นเวลา =================

    private function series(): array
    {
        $keys = $this->bucketKeys();
        $labels = array_map(fn ($k) => $this->bucketLabel($k), $keys);

        $delivered = $this->sumByBucket(
            DB::table('delivery_notes as dn')
                ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
                ->whereBetween('dn.delivery_date', [$this->from->toDateString(), $this->to->toDateString()]),
            'dn.delivery_date',
            'dnd.total_price'
        );

        $invoiced = $this->sumByBucket(
            DB::table('invoices')->whereBetween('created_at', [$this->from, $this->to]),
            'created_at',
            'total'
        );

        $paid = $this->sumByBucket(
            DB::table('payments')->whereBetween('paid_at', [$this->from->toDateString(), $this->to->toDateString()]),
            'paid_at',
            'amount'
        );

        return [
            'labels' => $labels,
            'delivered' => $this->align($keys, $delivered),
            'invoiced' => $this->align($keys, $invoiced),
            'paid' => $this->align($keys, $paid),
        ];
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
        return match ($this->bucket) {
            'day' => "DATE_FORMAT($col, '%Y-%m-%d')",
            'year' => "DATE_FORMAT($col, '%Y')",
            default => "DATE_FORMAT($col, '%Y-%m')",
        };
    }

    /** รายการ bucket ทั้งหมดในช่วง รวมช่องที่ไม่มีข้อมูล เพื่อให้กราฟไม่ขาดช่วง */
    private function bucketKeys(): array
    {
        $keys = [];
        $step = match ($this->bucket) {
            'day' => '1 day',
            'year' => '1 year',
            default => '1 month',
        };

        $cursor = match ($this->bucket) {
            'day' => $this->from->startOfDay(),
            'year' => $this->from->startOfYear(),
            default => $this->from->startOfMonth(),
        };

        // กันลูปยาวเกินไปถ้ามีใครยิง custom range ข้ามสิบปีแบบรายวัน
        $guard = 0;
        while ($cursor->lessThanOrEqualTo($this->to) && $guard++ < 3000) {
            $keys[] = match ($this->bucket) {
                'day' => $cursor->format('Y-m-d'),
                'year' => $cursor->format('Y'),
                default => $cursor->format('Y-m'),
            };
            $cursor = $cursor->add($step);
        }

        return $keys;
    }

    private function bucketLabel(string $key): string
    {
        $parts = explode('-', $key);

        return match ($this->bucket) {
            'day' => (int) $parts[2].' '.self::MONTHS_TH[(int) $parts[1]],
            'year' => $parts[0],
            default => self::MONTHS_TH[(int) $parts[1]].' '.substr($parts[0], 2),
        };
    }

    private function align(array $keys, array $map): array
    {
        return array_map(fn ($k) => round($map[$k] ?? 0, 2), $keys);
    }

    // ================= แยกตามแคมป์ / สินค้า =================

    /** รายได้ต่อแคมป์ — ใช้ delivery_notes.id_camp ซึ่งเป็นจุดเดียวที่ผูกงานเข้ากับแคมป์ */
    private function byCamp(): array
    {
        $rows = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->join('camps as c', 'c.id_camp', '=', 'dn.id_camp')
            ->whereNull('c.deleted_at')
            ->whereBetween('dn.delivery_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->groupBy('c.id_camp', 'c.code_camp', 'c.name_camp')
            ->orderByDesc('amount')
            ->limit(10)
            ->selectRaw('c.id_camp, c.code_camp, c.name_camp, SUM(dnd.total_price) as amount')
            ->get();

        return [
            'labels' => $rows->map(fn ($r) => $r->name_camp)->all(),
            'codes' => $rows->map(fn ($r) => $r->code_camp)->all(),
            'ids' => $rows->map(fn ($r) => $r->id_camp)->all(),
            'amounts' => $rows->map(fn ($r) => round((float) $r->amount, 2))->all(),
        ];
    }

    /**
     * รายได้ต่อสินค้า — รวมตามชื่อสินค้า ไม่ใช่ id
     * เพราะในฐานข้อมูลมีสินค้าชื่อซ้ำกันคนละ id (เช่น หินกรวด id 3 กับ 4)
     * ถ้าแยกตาม id กราฟจะโผล่สองแท่งทั้งที่เป็นของอย่างเดียวกัน
     */
    private function byProduct(): array
    {
        $rows = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as dnd', 'dnd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->join('products as p', 'p.id_product', '=', 'dnd.id_product')
            ->whereBetween('dn.delivery_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->groupBy('p.name_product')
            ->orderByDesc('amount')
            ->limit(10)
            ->selectRaw('p.name_product, SUM(dnd.total_price) as amount, SUM(dnd.quantity) as qty')
            ->get();

        return [
            'labels' => $rows->map(fn ($r) => $r->name_product)->all(),
            'amounts' => $rows->map(fn ($r) => round((float) $r->amount, 2))->all(),
            'qty' => $rows->map(fn ($r) => round((float) $r->qty, 2))->all(),
        ];
    }
}
