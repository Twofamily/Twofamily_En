<?php

/*
|--------------------------------------------------------------------------
| Breadcrumb labels
|--------------------------------------------------------------------------
| สร้าง breadcrumb อัตโนมัติจากชื่อ route เช่น
|   quotations.create  →  หน้าหลัก / ใบเสนอราคา / สร้างใบเสนอราคา
|
| resources : ส่วนหน้าของชื่อ route (ก่อนจุดตัวสุดท้าย) → ชื่อภาษาไทย
| actions   : ส่วนท้ายของชื่อ route → ข้อความ (:resource จะถูกแทนด้วยชื่อ resource)
|             ถ้าไม่มีใน actions จะไม่แสดงระดับที่ 3
| skip      : route ที่ไม่ต้องแสดง breadcrumb
|
| ตรวจชื่อ route จริงได้ด้วย  php artisan route:list --name=index
*/

return [

    'home' => [
        'label' => 'หน้าหลัก',
        'route' => 'dashboard',
    ],

    'resources' => [
        // เอกสาร
        'quotations'     => 'ใบเสนอราคา',
        'sales_orders'   => 'ใบสั่งขาย',
        'salesorders'    => 'ใบสั่งขาย',
        'camps'          => 'แคมป์งาน',
        'delivery_notes' => 'ใบส่งของ',
        'invoices'       => 'ใบแจ้งหนี้',
        'billing_notes'  => 'ใบวางบิล',
        'receipts'       => 'ใบเสร็จ',

        // ข้อมูลหลัก
        'customers'      => 'ลูกค้า',
        'product_types'  => 'ประเภทสินค้า',
        'products'       => 'สินค้า',
        'truck_brands'   => 'ยี่ห้อรถ',
        'truck_models'   => 'รุ่นรถ',
        'trucks'         => 'รถบรรทุก',
        'drivers'        => 'พนักงานขับรถ',

        // ระบบ
        'users'          => 'ผู้ใช้งาน',
        'settings'       => 'ตั้งค่าบริษัท',
        'profile'        => 'โปรไฟล์',
    ],

    'actions' => [
        'create' => 'สร้าง:resource',
        'edit'   => 'แก้ไข:resource',
        'show'   => 'รายละเอียด:resource',
    ],

    'skip' => [
        'dashboard',
    ],

];