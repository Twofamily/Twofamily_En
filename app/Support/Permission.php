<?php

namespace App\Support;

/**
 * ระบบสิทธิ์รายหน้า
 *
 * แต่ละหน้า (โมดูล) มีสิทธิ์ 3 ระดับ
 *   none = เข้าไม่ได้ (ซ่อนเมนู)
 *   view = ดูรายการ / ดูรายละเอียด / เปิด PDF ได้
 *   edit = ดู + สร้าง / แก้ไข / ลบ / เปลี่ยนสถานะ
 *
 * สิทธิ์จริง = ค่าที่ต่ำกว่าระหว่าง "เพดานของ role" กับ "ค่าที่ตั้งให้รายคน"
 *   admin  → edit ทุกหน้าเสมอ (ตั้งรายคนไม่ได้)
 *   staff  → เพดาน edit
 *   viewer → เพดาน view (ตั้งเป็น edit ก็ไม่มีผล)
 */
class Permission
{
    public const NONE = 'none';
    public const VIEW = 'view';
    public const EDIT = 'edit';

    /** ระดับเป็นตัวเลข ใช้เปรียบเทียบมาก/น้อย */
    public const LEVELS = [
        self::NONE => 0,
        self::VIEW => 1,
        self::EDIT => 2,
    ];

    /** ชื่อระดับภาษาไทย สำหรับหัวตาราง */
    public const LEVEL_LABELS = [
        self::NONE => 'ไม่มีสิทธิ์',
        self::VIEW => 'ดูอย่างเดียว',
        self::EDIT => 'ดูและแก้ไข',
    ];

    /**
     * รายการหน้าทั้งหมด จัดกลุ่มตามเมนู
     * คีย์ต้องตรงกับส่วนหน้าจุดของชื่อ route เช่น quotations.index → quotations
     */
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
                'sales-orders'   => 'ใบสั่งขาย',
                'delivery-notes' => 'ใบส่งของ',
                'invoices'       => 'ใบแจ้งหนี้ และหลักฐานการชำระ',
                'receipts'       => 'ใบเสร็จ',
            ],
        ];
    }

    /** รายการหน้าแบบแบน [คีย์ => ชื่อ] */
    public static function modules(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    /**
     * route ที่ชื่อไม่ตรงกับชื่อหน้า ให้ชี้ไปหน้าที่ถูกต้อง
     * ใส่ได้ทั้งชื่อเต็ม และส่วนหน้าจุด (ชื่อเต็มจะถูกเช็กก่อน)
     */
    private const ALIASES = [
        // ชื่อเต็ม
        'customers.store.ajax' => 'quotations',   // เพิ่มลูกค้าใหม่จากฟอร์มใบเสนอราคา

        // ส่วนหน้าจุด
        'quotation'    => 'quotations',            // quotation.pdf
        'invoice'      => 'invoices',              // invoice.pdf
        'payments'     => 'invoices',              // แนบสลิปอยู่ในหน้าใบแจ้งหนี้
        'maintenances' => 'trucks',                // ปิดงานซ่อมรถ
    ];

    /** หาว่า route นี้เป็นของหน้าไหน คืน null ถ้าไม่ได้ควบคุมสิทธิ์ (เช่น dashboard) */
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

    /**
     * route นี้ต้องใช้สิทธิ์แก้ไขหรือไม่
     * - POST / PUT / PATCH / DELETE → แก้ไข
     * - GET ที่เป็นหน้า create / edit → แก้ไข
     * - GET อื่น ๆ (index, show, pdf, slip) → ดู
     */
    public static function routeNeedsEdit(?string $routeName, string $method): bool
    {
        if (! in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            return true;
        }

        $action = $routeName ? last(explode('.', $routeName)) : '';

        return in_array($action, ['create', 'edit'], true);
    }

    /** ค่าเริ่มต้นตาม role (ใช้ตอนยังไม่เคยตั้งรายคน) */
    public static function defaultFor(?string $role): string
    {
        return match ($role) {
            'admin', 'staff' => self::EDIT,
            default          => self::VIEW,
        };
    }

    /** แปลงระดับเป็นตัวเลข ค่าแปลก ๆ ถือว่า none */
    public static function toInt(?string $level): int
    {
        return self::LEVELS[$level] ?? 0;
    }

    /** กรองค่าที่ส่งมาจากฟอร์ม เก็บเฉพาะหน้าที่รู้จักและระดับที่ถูกต้อง */
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