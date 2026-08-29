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
