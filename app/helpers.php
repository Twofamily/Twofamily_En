<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting($key)
    {
        return Setting::where('key', $key)->value('value');
    }
}

if (!function_exists('customer_address')) {
    function customer_address($customer): string
    {
        if (!$customer) {
            return '-';
        }

        $parts = array_filter([
            $customer->address_detail,
            $customer->subdistrict ? 'ตำบล' . $customer->subdistrict : null,
            $customer->district ? 'อำเภอ' . $customer->district : null,
            $customer->province ? 'จังหวัด' . $customer->province : null,
            $customer->zipcode,
        ]);

        return $parts ? implode(' ', $parts) : '-';
    }
}

if (!function_exists('thai_date')) {
    /**
     * แสดงวันที่รูปแบบเดียวกันทั้งระบบ: วัน/เดือน/ปี (เช่น 24/10/2026)
     * รับได้ทั้ง Carbon, DateTime, string และ null (null/ค่าว่าง → $empty)
     * $withTime = true → 24/10/2026 14:30
     */
    function thai_date($value, bool $withTime = false, string $empty = '-'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        try {
            $date = $value instanceof \DateTimeInterface
                ? \Carbon\Carbon::instance($value)
                : \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return (string) $value;
        }

        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    }
}
