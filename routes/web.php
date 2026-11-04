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

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin,staff,viewer',
    CheckPermission::class,
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    Route::post('camps/{camp}/trucks', [CampController::class, 'assignTruck'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.assign');

    Route::patch('camps/{camp}/trucks/{assignment}/release', [CampController::class, 'releaseTruck'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.release');

    Route::patch('camps/{camp}/status', [CampController::class, 'changeStatus'])
        ->middleware('role:admin,staff')
        ->name('camps.status');

    Route::patch('camps/{camp}/trucks/{assignment}/driver', [CampController::class, 'changeDriver'])
        ->middleware('role:admin,staff')
        ->name('camps.trucks.driver');

    Route::resource('fuel_records', FuelRecordController::class)
    ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin,staff');

    Route::resource('quotations', QuotationController::class)
        ->except(['destroy'])
        ->middlewareFor(['create', 'store', 'edit', 'update'], 'role:admin,staff');

    Route::get('/quotation/{quotation}/pdf', [QuotationController::class, 'downloadPDF'])
        ->name('quotation.pdf');

    Route::patch('quotations/{quotation}/approve', [QuotationController::class, 'approve'])
        ->middleware('role:admin')
        ->name('quotations.approve');

    Route::patch('quotations/{quotation}/cancel', [QuotationController::class, 'cancel'])
        ->middleware('role:admin,staff')
        ->name('quotations.cancel');

    Route::get('delivery_notes/{deliveryNote}/pdf', [DeliveryNoteController::class, 'pdf'])
        ->name('delivery_notes.pdf')
        ->withTrashed();

    Route::resource('delivery_notes', DeliveryNoteController::class)
        ->parameters(['delivery_notes' => 'deliveryNote'])
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['create', 'store', 'destroy'], 'role:admin,staff')
        ->withTrashed(['show']);

    Route::get('sales_orders/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])
        ->name('sales_orders.pdf');

    Route::post('sales_orders/customers', [CustomerController::class, 'storeAjax'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.customers.store');

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

    Route::post('sales_orders/{salesOrder}/attach-camp', [SalesOrderController::class, 'attachCamp'])
        ->middleware('role:admin,staff')
        ->name('sales_orders.attachCamp');

    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middlewareFor(['create', 'store', 'destroy'], 'role:admin,staff');

    Route::patch('/invoices/{id}/bill-received', [InvoiceController::class, 'billReceived'])
        ->middleware('role:admin,staff')
        ->name('invoices.billReceived');

    Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoice.pdf');

    Route::post('/invoices/create/{deliveryNote}', [InvoiceController::class, 'createFromDeliveryNote'])
        ->middleware('role:admin,staff')
        ->name('invoices.createFromDeliveryNote');

    Route::post('/invoices/{id}/payments', [PaymentController::class, 'store'])
        ->middleware('role:admin,staff')
        ->name('payments.store');

    Route::get('/payments/{payment}/slip', [PaymentController::class, 'slip'])
        ->name('payments.slip');

    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('role:admin,staff')
        ->name('payments.destroy');

    Route::get('/receipts', [ReceiptController::class, 'index'])
        ->name('receipts.index');

    Route::get('/receipts/create', [ReceiptController::class, 'create'])
        ->middleware('role:admin,staff')
        ->name('receipts.create');

    Route::post('/receipts', [ReceiptController::class, 'store'])
        ->middleware('role:admin,staff')
        ->name('receipts.store');

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

    Route::post('/customers/ajax', [CustomerController::class, 'storeAjax'])
        ->middleware('role:admin,staff')
        ->name('customers.store.ajax');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin',
])->group(function () {
    $settingGroups = array_keys(config('settings.groups', []));

    Route::get('/settings/{group?}', [SettingController::class, 'edit'])
        ->whereIn('group', $settingGroups)
        ->name('settings.index');

    Route::put('/settings/{group}', [SettingController::class, 'update'])
        ->whereIn('group', $settingGroups)
        ->name('settings.update');

    Route::resource('users', UserController::class)->except(['show']);

    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggleStatus');

    Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.resetPassword');

    Route::post('users/{user}/signature', [UserController::class, 'updateSignature'])
        ->name('users.signature');
});