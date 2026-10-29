<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * ตัวเลือกจำนวนรายการต่อหน้าที่ระบบยอมรับ (ใช้ร่วมกันทุกหน้ารายการ)
     *
     * ประกาศไว้ที่ class แม่ เพื่อให้ทุกหน้ารายการใช้ชุดเดียวกัน
     * และกันไม่ให้ผู้ใช้ยัด ?per_page=999999 ผ่าน URL จนดึงข้อมูลทั้งตาราง
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** จำนวนรายการต่อหน้าเริ่มต้น */
    public const DEFAULT_PER_PAGE = 10;

    /**
     * อ่านจำนวนรายการต่อหน้าจาก query string แล้วตรวจกับ whitelist
     * ค่าไม่ถูกต้อง → ใช้ค่าเริ่มต้น
     */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', static::DEFAULT_PER_PAGE);

        return in_array($perPage, static::PER_PAGE_OPTIONS, true)
            ? $perPage
            : static::DEFAULT_PER_PAGE;
    }

    /** อ่านคำค้นหา (ตัดช่องว่างหัวท้าย) */
    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->input('search', ''));
    }

    /**
     * อ่านช่วงวันที่ (date_from / date_to) จาก query string
     * รูปแบบผิด หรือเป็นวันที่ไม่มีจริง (เช่น 2026-02-30) → null
     * ถ้าเลือกสลับกัน (ตั้งแต่ > ถึง) จะสลับให้เอง แทนที่จะได้ผลว่าง
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function dateRange(Request $request): array
    {
        $from = $this->dateInput($request, 'date_from');
        $to   = $this->dateInput($request, 'date_to');

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /** กรอง query ตามช่วงวันที่ของคอลัมน์ที่ระบุ (ค่าไหนว่างก็ไม่กรองฝั่งนั้น) */
    protected function applyDateRange($query, string $column, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate($column, '<=', $to));
    }

    /**
     * หาค่าสถานะที่ "ป้ายภาษาไทย" ตรงกับคำค้น
     * เพื่อให้พิมพ์ "อนุมัติ" ในช่องค้นหาช่องเดียวแล้วเจอเอกสารสถานะ approved ได้
     *
     * รองรับทั้ง ['draft' => 'ร่าง'] และ ['paid' => ['label' => 'ชำระครบแล้ว', ...]]
     */
    protected function statusesMatching(string $search, array $labels): array
    {
        if ($search === '') {
            return [];
        }

        return collect($labels)
            ->map(fn ($label) => is_array($label) ? ($label['label'] ?? '') : $label)
            ->filter(fn ($label) => mb_stripos($label, $search) !== false)
            ->keys()
            ->all();
    }

    /**
     * อ่านวันที่รูปแบบ Y-m-d จาก query string
     * รูปแบบผิดหรือเป็นวันที่ไม่มีจริง → null
     */
    protected function dateInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    /**
     * อ่านตัวกรองสถานะ แล้วตรวจกับรายการที่อนุญาต
     * ไม่อยู่ในรายการ → null (แสดงทุกสถานะ)
     */
    protected function statusFilter(Request $request, array $allowed, string $key = 'status'): ?string
    {
        $status = $request->input($key);

        return in_array($status, $allowed, true) ? $status : null;
    }

    /**
     * อ่านตัวกรองแบบช่วงตัวเลข (ต่ำสุด–สูงสุด) หลายช่องพร้อมกัน
     *
     * $defs = ['price' => ['label' => 'ราคาต่อหน่วย', 'unit' => 'บาท', 'step' => '1']]
     * อ่านจาก query string ?price_min=100&price_max=500
     *
     * ใส่ 'options' => [ค่าที่เลือกได้...] → แสดงเป็น dropdown ต่ำสุด/สูงสุดแทนช่องพิมพ์
     * (เลือกต่ำสุดแล้ว ช่องสูงสุดจะเหลือเฉพาะค่าที่ ≥ ต่ำสุด)
     *
     * ค่าที่ไม่ใช่ตัวเลข → ไม่กรองฝั่งนั้น
     * ใส่สลับกัน (ต่ำสุด > สูงสุด) → สลับให้เอง แทนที่จะได้ผลว่าง
     *
     * @return array<string, array{label: string, unit: ?string, step: string, min: ?string, max: ?string}>
     */
    protected function numberRanges(Request $request, array $defs): array
    {
        $ranges = [];

        foreach ($defs as $key => $def) {
            $min = $this->numberInput($request, "{$key}_min");
            $max = $this->numberInput($request, "{$key}_max");

            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                [$min, $max] = [$max, $min];
            }

            // ให้ค่าที่อ่านได้ตรงกับตัวเลือกใน dropdown (เช่น 10.00 กับ 10)
            $min = $min !== null ? $this->trimNumber($min) : null;
            $max = $max !== null ? $this->trimNumber($max) : null;

            $ranges[$key] = [
                'label' => $def['label'] ?? $key,
                'unit'  => $def['unit'] ?? null,
                'step'  => $def['step'] ?? 'any',
                'min'   => $min,
                'max'   => $max,
                // ตัวเลือกของ dropdown: ตัดค่าซ้ำ เรียงน้อย→มาก และตัดศูนย์ท้ายทศนิยม (10.00 → 10)
                'options' => isset($def['options'])
                    ? collect($def['options'])
                        ->filter(fn ($v) => is_numeric($v))
                        ->map(fn ($v) => $this->trimNumber($v))
                        ->unique()
                        ->sortBy(fn ($v) => (float) $v)
                        ->values()
                        ->all()
                    : null,
            ];
        }

        return $ranges;
    }

    /**
     * กรอง query ตามช่วงตัวเลขของคอลัมน์ (หรือ SQL expression) ที่ระบุ
     * ค่าไหนว่างก็ไม่กรองฝั่งนั้น
     *
     * $column เป็นชื่อคอลัมน์ธรรมดา หรือ DB::raw('...') สำหรับค่าที่ต้องคำนวณก็ได้
     */
    protected function applyNumberRange($query, $column, ?array $range)
    {
        if (! $range) {
            return $query;
        }

        $isRaw  = $column instanceof \Illuminate\Contracts\Database\Query\Expression;
        $grammar = $isRaw ? $query->getQuery()->getGrammar() : null;
        $sql    = $isRaw ? $column->getValue($grammar) : null;

        if ($range['min'] !== null) {
            $isRaw
                ? $query->whereRaw("({$sql}) >= ?", [$range['min']])
                : $query->where($column, '>=', $range['min']);
        }

        if ($range['max'] !== null) {
            $isRaw
                ? $query->whereRaw("({$sql}) <= ?", [$range['max']])
                : $query->where($column, '<=', $range['max']);
        }

        return $query;
    }

    /** 10.00 → "10", 3.50 → "3.5" ใช้แสดงและเทียบค่าใน dropdown */
    protected function trimNumber($value): string
    {
        $s = (string) $value;

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    /** อ่านตัวเลขจาก query string (รับ 1,500.50 ได้) — ไม่ใช่ตัวเลข → null */
    protected function numberInput(Request $request, string $key): ?string
    {
        $value = str_replace([',', ' '], '', (string) $request->input($key, ''));

        return is_numeric($value) ? $value : null;
    }
}