<?php

/*
|--------------------------------------------------------------------------
| ทะเบียนค่าตั้งค่าระบบ (Setting Registry)
|--------------------------------------------------------------------------
| ทุก key ที่ระบบใช้ต้องประกาศที่นี่ที่เดียว
|
| - group   : อยู่แท็บไหนในหน้าตั้งค่า
| - label   : ชื่อที่แสดงในฟอร์ม
| - type    : string | text | int | decimal | bool | select | image
| - default : ค่าที่ใช้เมื่อยังไม่มีในฐานข้อมูล
|             (ระบบจึงไม่พัง แม้ยังไม่ได้รัน seeder)
| - rules   : กฎ validation ตอนบันทึกจากหน้าตั้งค่า
| - options : ตัวเลือก (เฉพาะ type = select)
| - legacy  : key ชื่อเดิมที่ PDF ยังอ่านอยู่ (ช่วงเปลี่ยนผ่าน)
|             ตอนบันทึกจะเขียนค่าเดียวกันลง key เดิมด้วย
|             และ migration จะคัดลอกค่าเดิมมาใส่ key ใหม่ให้
|
| หมายเหตุ: key ใช้จุดคั่น เช่น doc.vat_rate
| ในฟอร์มต้องตั้ง name เป็น doc[vat_rate] ห้ามใช้ name="doc.vat_rate"
| เพราะ PHP จะแปลงจุดใน $_POST เป็นขีดล่างให้เอง
*/

/* ---------- ประเภทเอกสาร ----------
| - prefix : ตัวย่อเริ่มต้น (แก้ได้ในหน้าตั้งค่า)
| - model  : Model ของเอกสาร
| - column : คอลัมน์ที่เก็บเลขที่เอกสาร
|   ใช้ตรวจเลขสูงสุดที่มีอยู่จริง กันเลขชนกับข้อมูลเดิม
|   ใบวางบิลยังไม่มี model จึงเป็น null ไว้ก่อน
*/

$documentTypes = [
    'quotation'     => ['label' => 'ใบเสนอราคา',            'prefix' => 'QT',  'model' => App\Models\Quotation::class,    'column' => 'code_quot'],
    'sales_order'   => ['label' => 'ใบสั่งขาย',              'prefix' => 'SO',  'model' => App\Models\SalesOrder::class,   'column' => 'code_so'],
    'delivery_note' => ['label' => 'ใบส่งของ',               'prefix' => 'DN',  'model' => App\Models\DeliveryNote::class, 'column' => 'code_dn'],
    'invoice'       => ['label' => 'ใบแจ้งหนี้/ใบกำกับภาษี', 'prefix' => 'INV', 'model' => App\Models\Invoice::class,      'column' => 'code_inv'],
    'billing_note'  => ['label' => 'ใบวางบิล',               'prefix' => 'BN',  'model' => null,                            'column' => null],
    'receipt'       => ['label' => 'ใบเสร็จรับเงิน',          'prefix' => 'RC',  'model' => App\Models\Receipt::class,      'column' => 'code_rc'],
];

/* ---------- ข้อมูลบริษัท ---------- */

$company = [
    'company.name_th' => [
        'group'   => 'company',
        'label'   => 'ชื่อบริษัท (ไทย)',
        'type'    => 'string',
        'default' => 'บริษัท ทู แฟมิลี่ เอ็นจิเนียริ่ง จำกัด',
        'rules'   => ['required', 'string', 'max:255'],
        'legacy'  => ['company_name'],
    ],
    'company.name_en' => [
        'group'   => 'company',
        'label'   => 'ชื่อบริษัท (อังกฤษ)',
        'type'    => 'string',
        'default' => 'Two Family Engineering Co., Ltd.',
        'rules'   => ['nullable', 'string', 'max:255'],
    ],
    'company.tax_id' => [
        'group'   => 'company',
        'label'   => 'เลขประจำตัวผู้เสียภาษี',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'digits:13'],   // controller ตัดขีดและช่องว่างออกก่อนตรวจ
        'legacy'  => ['tax_id'],
    ],
    'company.branch' => [
        'group'   => 'company',
        'label'   => 'สาขา',
        'type'    => 'string',
        'default' => 'สำนักงานใหญ่',
        'rules'   => ['nullable', 'string', 'max:100'],
    ],
    'company.address' => [
        'group'   => 'company',
        'label'   => 'ที่อยู่',
        'type'    => 'text',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:500'],
        'legacy'  => ['company_address'],
    ],
    'company.phone' => [
        'group'   => 'company',
        'label'   => 'เบอร์โทรศัพท์',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:50'],
        'legacy'  => ['company_phone'],
    ],
    'company.email' => [
        'group'   => 'company',
        'label'   => 'อีเมล',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'email', 'max:255'],
    ],
    'company.logo' => [
        'group'   => 'company',
        'label'   => 'โลโก้',
        'type'    => 'image',
        'default' => null,   // เก็บ path ภายใต้ storage/app/public
        'rules'   => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
    ],
];

/* ---------- เอกสาร ---------- */

$document = [
    'doc.year_format' => [
        'group'   => 'document',
        'label'   => 'ปีในเลขที่เอกสาร',
        'type'    => 'select',
        'options' => ['be' => 'พ.ศ. (2569)', 'ce' => 'ค.ศ. (2026)'],
        'default' => 'be',
        'rules'   => ['required', 'in:be,ce'],
    ],
    'doc.reset_period' => [
        'group'   => 'document',
        'label'   => 'เริ่มนับเลขใหม่',
        'type'    => 'select',
        'options' => ['yearly' => 'ทุกปี', 'monthly' => 'ทุกเดือน', 'never' => 'ไม่เริ่มใหม่'],
        'default' => 'yearly',
        'rules'   => ['required', 'in:yearly,monthly,never'],
    ],
    'doc.running_digits' => [
        'group'   => 'document',
        'label'   => 'จำนวนหลักของเลขรัน',
        'type'    => 'int',
        'default' => 4,
        'rules'   => ['required', 'integer', 'between:3,6'],
    ],
    'doc.vat_rate' => [
        'group'   => 'document',
        'label'   => 'อัตราภาษีมูลค่าเพิ่ม (%)',
        'type'    => 'decimal',
        'default' => 7,      // เก็บเป็นเปอร์เซ็นต์เสมอ ไม่ใช่ 0.07
        'rules'   => ['required', 'numeric', 'between:0,30'],
    ],
    'doc.quotation_valid_days' => [
        'group'   => 'document',
        'label'   => 'อายุใบเสนอราคา (วัน)',
        'type'    => 'int',
        'default' => 30,
        'rules'   => ['required', 'integer', 'between:1,365'],
    ],
    'doc.credit_days' => [
        'group'   => 'document',
        'label'   => 'เครดิตเทอมเริ่มต้น (วัน)',
        'type'    => 'int',
        'default' => 30,
        'rules'   => ['required', 'integer', 'between:0,180'],
        // ของเดิมแยกเครดิตใบแจ้งหนี้กับใบเสนอราคา รวมเป็นค่าเดียว
        // migration ใช้ค่าของใบแจ้งหนี้ก่อน
        'legacy'  => ['credit_term', 'quotation_credit_term'],
    ],
    'doc.show_signature' => [
        'group'   => 'document',
        'label'   => 'แสดงลายเซ็นในเอกสาร PDF',
        'type'    => 'bool',
        'default' => true,
        'rules'   => ['boolean'],
    ],
];

/* ตัวย่อ และหมายเหตุท้ายเอกสาร สร้างให้ครบทุกประเภทอัตโนมัติ */
$legacyNotes = [
    'quotation' => ['quotation_note'],
    'invoice'   => ['invoice_note'],
];

foreach ($documentTypes as $type => $info) {
    $document["doc.prefix.{$type}"] = [
        'group'   => 'document',
        'label'   => "ตัวย่อ{$info['label']}",
        'type'    => 'string',
        'default' => $info['prefix'],
        'rules'   => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
    ];

    $document["doc.note.{$type}"] = [
        'group'   => 'document',
        'label'   => "หมายเหตุท้าย{$info['label']}",
        'type'    => 'text',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:1000'],
        'legacy'  => $legacyNotes[$type] ?? [],
    ];
}

/* ---------- การรับชำระเงิน ---------- */

$payment = [
    'payment.bank_name' => [
        'group'   => 'payment',
        'label'   => 'ธนาคาร',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:100'],
        'legacy'  => ['bank_name'],
    ],
    'payment.account_name' => [
        'group'   => 'payment',
        'label'   => 'ชื่อบัญชี',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:255'],
        'legacy'  => ['bank_account_name'],
    ],
    'payment.account_no' => [
        'group'   => 'payment',
        'label'   => 'เลขที่บัญชี',
        'type'    => 'string',
        'default' => '',
        'rules'   => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-\s]+$/'],
        'legacy'  => ['bank_account'],
    ],
    'payment.require_slip' => [
        'group'   => 'payment',
        'label'   => 'ต้องแนบหลักฐานการชำระก่อนออกใบเสร็จ',
        'type'    => 'bool',
        'default' => true,
        'rules'   => ['boolean'],
    ],
    'payment.slip_max_kb' => [
        'group'   => 'payment',
        'label'   => 'ขนาดไฟล์สลิปสูงสุด (KB)',
        'type'    => 'int',
        'default' => 5120,
        'rules'   => ['required', 'integer', 'between:500,10240'],
    ],
];

/* ---------- ทั่วไป ---------- */

$general = [
    'general.per_page' => [
        'group'   => 'general',
        'label'   => 'จำนวนแถวต่อหน้าเริ่มต้น',
        'type'    => 'select',
        'options' => ['10' => '10', '25' => '25', '50' => '50', '100' => '100'],
        'default' => '10',
        'rules'   => ['required', 'in:10,25,50,100'],
    ],
];

return [

    'groups' => [
        'company'  => ['label' => 'ข้อมูลบริษัท',   'icon' => 'bi-building'],
        'document' => ['label' => 'เอกสาร',        'icon' => 'bi-file-earmark-text'],
        'payment'  => ['label' => 'การรับชำระเงิน', 'icon' => 'bi-bank'],
        'general'  => ['label' => 'ทั่วไป',         'icon' => 'bi-sliders'],
    ],

    'document_types' => $documentTypes,

    'definitions' => array_merge($company, $document, $payment, $general),

];