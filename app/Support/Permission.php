<?php

namespace App\Support;

class Permission
{
    public const NONE = 'none';
    public const VIEW = 'view';
    public const EDIT = 'edit';

    public const LEVELS = [
        self::NONE => 0,
        self::VIEW => 1,
        self::EDIT => 2,
    ];

    public const LEVEL_LABELS = [
        self::NONE => 'ไม่มีสิทธิ์',
        self::VIEW => 'ดูอย่างเดียว',
        self::EDIT => 'ดูและแก้ไข',
    ];

    public static function groups(): array
    {
        return [
            'ข้อมูลหลัก' => [
                'camps'         => 'แคมป์งาน',
                'customers'     => 'ลูกค้า',
                'drivers'       => 'พนักงานขับรถ',
                'product_types' => 'ประเภทสินค้า',
                'products'      => 'สินค้า',
                'fuel_records'  => 'ต้นทุนค่าน้ำมัน',
            ],
            'รถบรรทุก' => [
                'truck_brands'  => 'ยี่ห้อรถบรรทุก',
                'truck_models'  => 'รุ่นรถบรรทุก',
                'trucks'        => 'รถบรรทุกในบริษัท',
            ],
            'เอกสาร' => [
                'quotations'     => 'ใบเสนอราคา',
                'sales_orders'   => 'ใบสั่งขาย',
                'delivery_notes' => 'ใบส่งของ',
                'invoices'       => 'ใบแจ้งหนี้ และหลักฐานการชำระ',
                'receipts'       => 'ใบเสร็จ',
            ],
        ];
    }

    public static function modules(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    private const ALIASES = [
        'customers.store.ajax' => 'quotations',

        'quotation'    => 'quotations',
        'invoice'      => 'invoices',
        'payments'     => 'invoices',
        'maintenances' => 'trucks',
    ];

    public static function moduleFromRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (isset(self::ALIASES[$routeName])) {
            return self::ALIASES[$routeName];
        }

        $prefix = explode('.', $routeName)[0];
        $prefix = self::ALIASES[$prefix] ?? $prefix;

        return array_key_exists($prefix, self::modules()) ? $prefix : null;
    }

    public static function routeNeedsEdit(?string $routeName, string $method): bool
    {
        if (! in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            return true;
        }

        $action = $routeName ? last(explode('.', $routeName)) : '';

        return in_array($action, ['create', 'edit'], true);
    }

    public static function defaultFor(?string $role): string
    {
        return match ($role) {
            'admin', 'staff' => self::EDIT,
            default          => self::VIEW,
        };
    }

    public static function toInt(?string $level): int
    {
        return self::LEVELS[$level] ?? 0;
    }

    public static function clean(array $input): array
    {
        $clean = [];

        foreach (array_keys(self::modules()) as $module) {
            if (isset($input[$module]) && array_key_exists($input[$module], self::LEVELS)) {
                $clean[$module] = $input[$module];
            }
        }

        return $clean;
    }
}