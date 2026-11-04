<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Breadcrumb
{
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

        $parts  = explode('.', $name);
        $action = count($parts) > 1 ? array_pop($parts) : 'index';
        $prefix = implode('.', $parts);
        $key    = end($parts);

        $resourceLabel = $cfg['resources'][$key] ?? Str::headline($key);

        $items = [];

        $homeRoute = $cfg['home']['route'] ?? 'dashboard';
        $items[] = [
            'label' => $cfg['home']['label'] ?? 'หน้าหลัก',
            'url'   => Route::has($homeRoute) ? route($homeRoute) : url('/'),
            'icon'  => 'bi-house-door',
        ];

        $indexRoute = $prefix . '.index';
        $items[] = [
            'label' => $resourceLabel,
            'url'   => Route::has($indexRoute) ? route($indexRoute) : null,
            'icon'  => null,
        ];

        if ($action !== 'index' && ! empty($cfg['actions'][$action])) {
            $items[] = [
                'label' => str_replace(':resource', $resourceLabel, $cfg['actions'][$action]),
                'url'   => null,
                'icon'  => null,
            ];
        }

        $items[array_key_last($items)]['url'] = null;

        return $items;
    }
}