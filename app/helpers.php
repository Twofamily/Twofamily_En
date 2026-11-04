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

        $isBangkok = in_array($customer->province, ['กรุงเทพมหานคร', 'กรุงเทพฯ'], true);
        $district  = (string) $customer->district;
        $districtPrefix = $isBangkok ? 'เขต' : 'อำเภอ';

        $parts = array_filter([
            $customer->address_no ?? null,
            filled($customer->moo ?? null) ? 'หมู่ ' . $customer->moo : null,
            $customer->address_detail,
            filled($customer->soi ?? null) ? 'ซอย' . $customer->soi : null,
            filled($customer->road ?? null) ? 'ถนน' . $customer->road : null,
            $customer->subdistrict ? ($isBangkok ? 'แขวง' : 'ตำบล') . $customer->subdistrict : null,
            $district !== ''
                ? (str_starts_with($district, 'เขต') || str_starts_with($district, 'อำเภอ') ? $district : $districtPrefix . $district)
                : null,
            $customer->province ? ($isBangkok ? $customer->province : 'จังหวัด' . $customer->province) : null,
            $customer->zipcode,
        ]);

        return $parts ? implode(' ', $parts) : '-';
    }
}

if (!function_exists('customer_tax_line')) {
    function customer_tax_line($customer): string
    {
        if (!$customer || blank($customer->tax_id)) {
            return '-';
        }

        $branch = method_exists($customer, 'branchLabel') ? $customer->branchLabel() : null;

        return $customer->formattedTaxId() . ($branch ? ' (' . $branch . ')' : '');
    }
}

if (!function_exists('thai_date')) {
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
