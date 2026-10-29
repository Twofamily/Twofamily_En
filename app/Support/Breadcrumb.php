<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Breadcrumb
{
    /**
     * สร้างรายการ breadcrumb จาก route ปัจจุบัน
     *
     * @return array<int, array{label: string, url: ?string, icon: ?string}>
     */
    public static function build(): array
    {
        $name = Route::currentRouteName();

        if (! $name) {
            return [];
        }

        $cfg = config('breadcrumbs', []);

        if (in_array($name, $cfg['skip'] ?? [], true)) {
            return [];
        }

        // แยกชื่อ route: "quotations.create" → resource = quotations, action = create
        $parts  = explode('.', $name);
        $action = count($parts) > 1 ? array_pop($parts) : 'index';
        $prefix = implode('.', $parts);
        $key    = end($parts);

        $resourceLabel = $cfg['resources'][$key] ?? Str::headline($key);

        $items = [];

        // 1) หน้าหลัก
        $homeRoute = $cfg['home']['route'] ?? 'dashboard';
        $items[] = [
            'label' => $cfg['home']['label'] ?? 'หน้าหลัก',
            'url'   => Route::has($homeRoute) ? route($homeRoute) : url('/'),
            'icon'  => 'bi-house-door',
        ];

        // 2) หน้ารายการของ resource
        $indexRoute = $prefix . '.index';
        $items[] = [
            'label' => $resourceLabel,
            'url'   => Route::has($indexRoute) ? route($indexRoute) : null,
            'icon'  => null,
        ];

        // 3) หน้าย่อย (create / edit / show ...)
        if ($action !== 'index' && ! empty($cfg['actions'][$action])) {
            $items[] = [
                'label' => str_replace(':resource', $resourceLabel, $cfg['actions'][$action]),
                'url'   => null,
                'icon'  => null,
            ];
        }

        // ตัวสุดท้ายคือหน้าปัจจุบัน ไม่ต้องเป็นลิงก์
        $items[array_key_last($items)]['url'] = null;

        return $items;
    }
}