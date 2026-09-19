<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    TruckController,
    DriverController,
    DashboardController,
    ProductController,
    CustomerController,
    InvoiceController,
    FuelRecordController,
    ProductTypeController,
    TransportJobController,
    QuotationController,
    SalesOrderController,
    TruckBrandController,
    TruckModelController,
    SettingController,
    ReceiptController,
    DeliveryNoteController,
    CampController,
    UserController,
    CompanySettingController
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
|==========================================================================
*/
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin,staff,viewer',
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


    /* ==================== Operations ==================== */

    // Route::resource('transport-jobs', TransportJobController::class)
    //     ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('fuel_records', FuelRecordController::class)
    ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');


    /* ==================== ใบเสนอราคา ==================== */

    Route::resource('quotations', QuotationController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

        // ต้องมาก่อน resource ไม่งั้น {deliveryNote} จะกิน "pdf"
    Route::get('delivery-notes/{deliveryNote}/pdf', [DeliveryNoteController::class, 'pdf'])
        ->name('delivery-notes.pdf');

    Route::resource('delivery-notes', DeliveryNoteController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['create', 'store', 'destroy'], 'role:admin,staff');

    Route::get('/quotation/{quotation}/pdf', [QuotationController::class, 'downloadPDF'])
        ->name('quotation.pdf');

    Route::patch('quotations/{quotation}/approve', [QuotationController::class, 'approve'])
        ->middleware('role:admin,staff')
        ->name('quotations.approve');

    Route::patch('quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])
        ->middleware('role:admin,staff')
        ->name('quotations.cancel');

        /* ==================== ใบสั่งขาย ==================== */

    // ต้องมาก่อน resource ไม่งั้น {salesOrder} จะกินคำว่า "pdf"
    Route::get('sales-orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])
        ->name('sales-orders.pdf');

    Route::resource('sales-orders', SalesOrderController::class)
        ->parameters(['sales-orders' => 'salesOrder'])
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::patch('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])
        ->middleware('role:admin,staff')
        ->name('sales-orders.confirm');

    Route::patch('sales-orders/{salesOrder}/revert', [SalesOrderController::class, 'revertToDraft'])
        ->middleware('role:admin,staff')
        ->name('sales-orders.revert');

    Route::patch('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])
        ->middleware('role:admin,staff')
        ->name('sales-orders.cancel');

    Route::patch('sales-orders/{salesOrder}/close', [SalesOrderController::class, 'close'])
        ->middleware('role:admin,staff')
        ->name('sales-orders.close');

    /* ==================== ใบแจ้งหนี้ ==================== */

    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show', 'destroy'])
        ->middlewareFor(['destroy'], 'role:admin,staff');

    Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoice.pdf');

    Route::post('/invoices/create/{deliveryNote}', [InvoiceController::class, 'createFromDeliveryNote'])
        ->middleware('role:admin,staff')
        ->name('invoices.createFromDeliveryNote');

    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay'])
        ->middleware('role:admin,staff')
        ->name('invoices.pay');


    /* ==================== ใบเสร็จ ==================== */

    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->name('receipts.index');

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
    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings.index');

    Route::get('/settings/documents', [SettingController::class, 'documents'])
        ->name('settings.documents');

    Route::get('/settings/documents/quotation', [SettingController::class, 'quotation'])
        ->name('settings.quotation');

    Route::post('/settings/documents/quotation', [SettingController::class, 'quotationUpdate'])
        ->name('settings.quotation.update');

    Route::get('/settings/documents/invoice', [SettingController::class, 'invoice'])
        ->name('settings.invoice');

    Route::post('/settings/documents/invoice', [SettingController::class, 'invoiceUpdate'])
        ->name('settings.invoice.update');

    Route::get('/settings/documents/receipt', fn() => view('settings.documents.receipt', [
        'settings' => \App\Models\Setting::pluck('value', 'key'),
    ]))->name('settings.documents.receipt');

    Route::post('/settings/update', [SettingController::class, 'update'])
        ->name('settings.update');

    Route::post('/settings/receipt', [SettingController::class, 'update'])
        ->name('settings.receipt.update');


    /* ---------- จัดการผู้ใช้งาน ---------- */
    Route::resource('users', UserController::class)->except(['show']);

    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggleStatus');

    Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.resetPassword');
});
