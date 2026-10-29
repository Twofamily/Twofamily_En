<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\{
    TruckController,
    DriverController,
    DashboardController,
    ProductController,
    CustomerController,
    InvoiceController,
    FuelRecordController,
    ProductTypeController,
    QuotationController,
    SalesOrderController,
    TruckBrandController,
    TruckModelController,
    SettingController,
    ReceiptController,
    DeliveryNoteController,
    CampController,
    UserController,
    CompanySettingController,
    PaymentController
};

/*
|--------------------------------------------------------------------------
| หน้าแรก
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('welcome');


/*
|==========================================================================
|  กลุ่มหลัก : ผู้ใช้ที่ล็อกอินแล้วทุกระดับ (admin / staff / viewer)
|
|  หลักการ:
|  - ประกาศ resource ครั้งเดียว ครบทุก action  (ป้องกัน route ชนกัน)
|  - action ที่แก้ไขข้อมูล กำหนดสิทธิ์เพิ่มด้วย middlewareFor()
|  - CheckPermission ตรวจสิทธิ์รายหน้าของแต่ละคนต่ออีกชั้น
|    (ต้องอยู่หลัง role:... เพราะ role เช็กล็อกอินและบัญชีระงับให้ก่อน)
|==========================================================================
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin,staff,viewer',
    CheckPermission::class,
])->group(function () {

    /* ---------- Dashboard ---------- */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');



    /* ==================== Master Data ==================== */

    Route::resource('customers', CustomerController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('drivers', DriverController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('products', ProductController::class)
        ->except(['show'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('product_types', ProductTypeController::class)
        ->except(['show'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('truck_brands', TruckBrandController::class)
        ->except(['show'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('truck_models', TruckModelController::class)
        ->except(['show'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('camps', CampController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    /* รถบรรทุก : show ถูกแยกเป็น route พิเศษ ด้านล่าง */
    Route::resource('trucks', TruckController::class)
        ->except(['show'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::get('trucks/{truck}/detail', [TruckController::class, 'show'])
        ->name('trucks.show');

    Route::patch('trucks/{truck}/status', [TruckController::class, 'updateStatus'])
        ->middleware('role:admin,staff')
        ->name('trucks.status');

    Route::patch('maintenances/{maintenance}/finish', [TruckController::class, 'finishMaintenance'])
        ->middleware('role:admin,staff')
        ->name('maintenances.finish');

    /* ==================== มอบหมายรถเข้าแคมป์ ==================== */

    Route::post('camps/{camp}/trucks', [CampController::class, 'assignTruck'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.assign');

    Route::patch('camps/{camp}/trucks/{assignment}/release', [CampController::class, 'releaseTruck'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.release');

    // เปลี่ยนสถานะแคมป์ทีละขั้น (เตรียมงาน → ดำเนินงาน → ส่งครบ → ปิดแคมป์)
    Route::patch('camps/{camp}/status', [CampController::class, 'changeStatus'])
        ->middleware('role:admin,staff')
        ->name('camps.status');

    Route::patch('camps/{camp}/trucks/{assignment}/driver', [CampController::class, 'changeDriver'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.driver');


    /* ==================== Operations ==================== */

    Route::resource('fuel_records', FuelRecordController::class)
    ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');


    /* ==================== ใบเสนอราคา ==================== */

    // เอกสารไม่มีการลบ — ใช้ "ยกเลิก" แทน (quotations.cancel)
    Route::resource('quotations', QuotationController::class)
        ->except(['destroy'])
        ->middlewareFor(['create', 'store', 'edit', 'update'], 'role:admin,staff');

    Route::get('/quotation/{quotation}/pdf', [QuotationController::class, 'downloadPDF'])
        ->name('quotation.pdf');

    // อนุมัติได้เฉพาะผู้ดูแลระบบ / ผู้จัดการ (staff สร้าง/แก้ใบร่างได้อย่างเดียว)
    Route::patch('quotations/{quotation}/approve', [QuotationController::class, 'approve'])
        ->middleware('role:admin')
        ->name('quotations.approve');

    Route::patch('quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])
        ->middleware('role:admin,staff')
        ->name('quotations.cancel');

    /* ==================== ใบส่งของ ==================== */

    // ต้องมาก่อน resource ไม่งั้น {deliveryNote} จะกิน "pdf"
    // ใบที่ยกเลิกแล้วยังเปิดดู / พิมพ์ได้ (withTrashed) — destroy = ยกเลิกใบส่งของ ไม่ได้ลบ
    Route::get('delivery_notes/{deliveryNote}/pdf', [DeliveryNoteController::class, 'pdf'])
        ->name('delivery_notes.pdf')
        ->withTrashed();

    Route::resource('delivery_notes', DeliveryNoteController::class)
        ->parameters(['delivery_notes' => 'deliveryNote'])
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['create', 'store', 'destroy'], 'role:admin,staff')
        ->withTrashed(['show']);

    /* ==================== ใบสั่งขาย ==================== */

    // ต้องมาก่อน resource ไม่งั้น {salesOrder} จะกินคำว่า "pdf"
    Route::get('sales_orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])
        ->name('sales_orders.pdf');

    // เพิ่มลูกค้าใหม่จากหน้าต่างใบสั่งตรง — ใช้ storeAjax ตัวเดิม แต่เช็กสิทธิ์ตามหน้าใบสั่งขาย
    // (customers.store.ajax ผูกสิทธิ์กับหน้าใบเสนอราคา คนที่ดูแลแค่ใบสั่งขายจะโดน 403)
    Route::post('sales_orders/customers', [CustomerController::class, 'storeAjax'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.customers.store');

    // เอกสารไม่มีการลบ — ใช้ "ยกเลิก" แทน (sales_orders.cancel)
    Route::resource('sales_orders', SalesOrderController::class)
        ->parameters(['sales_orders' => 'salesOrder'])
        ->except(['destroy'])
        ->middlewareFor(['create', 'store', 'edit', 'update'], 'role:admin,staff');

    Route::patch('sales_orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.confirm');

    Route::patch('sales_orders/{salesOrder}/revert', [SalesOrderController::class, 'revertToDraft'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.revert');

    Route::patch('sales_orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.cancel');

    Route::patch('sales_orders/{salesOrder}/close', [SalesOrderController::class, 'close'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.close');

    // เพิ่มใบสั่งขายเข้าแคมป์เดิม (ลูกค้าสั่งเพิ่มในงานเดิม)
    // ถ้าเปิดแคมป์ใหม่ ใช้ camps.create?sales_order=...
    Route::post('sales_orders/{salesOrder}/attach-camp', [SalesOrderController::class, 'attachCamp'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.attachCamp');

    /* ==================== ใบแจ้งหนี้ ==================== */

    // ใบแจ้งหนี้ / ใบวางบิล — 1 ใบรวมใบส่งของได้หลายใบ (create = หน้าเลือกใบส่งของ)
    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['create', 'store', 'destroy'], 'role:admin,staff');

    // บันทึกวันที่ลูกค้าเซ็นรับบิล
    Route::patch('/invoices/{id}/bill-received', [InvoiceController::class, 'billReceived'])
        ->middleware('role:admin,staff')
        ->name('invoices.billReceived');

    Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoice.pdf');

    // ลิงก์เดิมจากหน้าใบส่งของ → ส่งต่อไปหน้าเลือกใบส่งของ (ติ๊กใบนั้นไว้ให้)
    Route::post('/invoices/create/{deliveryNote}', [InvoiceController::class, 'createFromDeliveryNote'])
        ->middleware('role:admin,staff')
        ->name('invoices.createFromDeliveryNote');

    /* ==================== การชำระเงิน (หลักฐาน) ==================== */

    Route::post('/invoices/{id}/payments', [PaymentController::class, 'store'])
        ->middleware('role:admin,staff')
        ->name('payments.store');

    Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])
        ->name('payments.slip');

    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('role:admin,staff')
        ->name('payments.destroy');


    /* ==================== ใบเสร็จ ==================== */

    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->name('receipts.index');

    // ใบเสร็จขายสด (ไม่ต้องมีใบแจ้งหนี้) — กรอกในหน้าต่างของหน้ารายการ
    // ต้องมาก่อน /receipts/{id} ไม่งั้น {id} จะกินคำว่า "create"
    Route::get('/receipts/create', [ReceiptController::class, 'create'])
        ->middleware('role:admin,staff')
        ->name('receipts.create');

    Route::post('/receipts', [ReceiptController::class, 'store'])
        ->middleware('role:admin,staff')
        ->name('receipts.store');

    // เพิ่มลูกค้าใหม่จากหน้าขายสด — ใช้ storeAjax ตัวเดิม แต่เช็กสิทธิ์ตามหน้าใบเสร็จ
    // (customers.store.ajax ผูกสิทธิ์กับหน้าใบเสนอราคา คนที่ดูแลแค่ใบเสร็จจะโดน 403)
    Route::post('/receipts/customers', [CustomerController::class, 'storeAjax'])
        ->middleware('role:admin,staff')
        ->name('receipts.customers.store');

    Route::get('/receipts/pdf/{id}', [ReceiptController::class, 'pdf'])
        ->name('receipts.pdf');

    Route::get('/receipts/{id}', [ReceiptController::class, 'show'])
        ->name('receipts.show');

    Route::post('/receipts/create/{id}', [ReceiptController::class, 'createFromInvoice'])
        ->middleware('role:admin,staff')
        ->name('receipts.createFromInvoice');

    Route::delete('/receipts/{id}', [ReceiptController::class, 'destroy'])
        ->middleware('role:admin,staff')
        ->name('receipts.destroy');


    /* ==================== AJAX ==================== */

    Route::post('/customers/ajax', [CustomerController::class, 'storeAjax'])
        ->middleware('role:admin,staff')
        ->name('customers.store.ajax');
});


/*
|==========================================================================
|  ผู้ดูแลระบบเท่านั้น (admin)
|==========================================================================
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin',
])->group(function () {

    /* ---------- ตั้งค่าระบบ ---------- */
    // แท็บที่มีอ่านจาก config/settings.php → groups
    // เข้า /settings เฉย ๆ จะเปิดแท็บข้อมูลบริษัท
    $settingGroups = array_keys(config('settings.groups', []));

    Route::get('/settings/{group?}', [SettingController::class, 'edit'])
        ->whereIn('group', $settingGroups)
        ->name('settings.index');

    Route::put('/settings/{group}', [SettingController::class, 'update'])
        ->whereIn('group', $settingGroups)
        ->name('settings.update');


    /* ---------- จัดการผู้ใช้งาน ---------- */
    Route::resource('users', UserController::class)->except(['show']);

    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggleStatus');

    Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.resetPassword');

    // ลายเซ็นอิเล็กทรอนิกส์ + ตำแหน่ง ที่พิมพ์ในกรอบลายเซ็นของ PDF
    Route::post('users/{user}/signature', [UserController::class, 'updateSignature'])
        ->name('users.signature');
});