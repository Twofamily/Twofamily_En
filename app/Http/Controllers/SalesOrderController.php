<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /** อัตราภาษีมูลค่าเพิ่ม */
    private const VAT_RATE = 0.07;

    public function index(Request $request)
    {
        $q          = $request->q;
        $status     = $request->status;
        $customerId = $request->customer;

        $salesOrders = SalesOrder::with(['customer', 'quotation', 'camp'])
            ->when($q, fn($query) => $query->where(fn($x) =>
                $x->where('code_so', 'like', "%{$q}%")
                  ->orWhere('po_number', 'like', "%{$q}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name_customer', 'like', "%{$q}%"))
            ))
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($customerId, fn($query) => $query->where('id_customer', $customerId))
            ->latest('id_so')
            ->paginate(10)
            ->withQueryString();

        $customers = Customer::orderBy('name_customer')->get();

        return view('sales_orders.index', compact(
            'salesOrders', 'customers', 'q', 'status', 'customerId'
        ));
    }

    /**
     * เลือกใบเสนอราคาก่อน แล้วค่อยสร้างใบสั่งขาย
     * ถ้าไม่ส่ง quotation มา จะแสดงรายการให้เลือก
     */
    public function create(Request $request)
    {
        $quotation = $request->quotation
            ? Quotation::with(['customer', 'details.product'])->find($request->quotation)
            : null;

        // กันการเปิดใบสั่งขายซ้ำจากใบเสนอราคาเดิม
        if ($quotation && ! $quotation->canCreateSalesOrder()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', $quotation->hasSalesOrder()
                    ? 'ใบเสนอราคานี้ออกใบสั่งขายไปแล้ว'
                    : 'ใบเสนอราคานี้ยังไม่ได้รับการอนุมัติ');
        }

        return view('sales_orders.create', [
            'quotation'  => $quotation,
            'quotations' => Quotation::with('customer')
                                ->convertible()
                                ->latest('id_quot')
                                ->get(),
            'products'   => Product::orderBy('name_product')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSalesOrder($request);

        if ($error = $this->checkQuotation($data)) {
            return back()->withInput()->with('error', $error);
        }

        $totals = $this->calculateTotals($data['items'], $data['discount'] ?? 0);

        $salesOrder = DB::transaction(function () use ($data, $totals) {

            $so = SalesOrder::create([
                'code_so'      => SalesOrder::generateCode(),
                'id_quot'      => $data['id_quot'] ?? null,
                'id_customer'  => $data['id_customer'],
                'order_date'   => $data['order_date'],
                'due_date'     => $data['due_date'] ?? null,
                'po_number'    => $data['po_number'] ?? null,
                'status'       => 'draft',
                'subtotal'     => $totals['subtotal'],
                'discount'     => $totals['discount'],
                'vat_amount'   => $totals['vat_amount'],
                'total_amount' => $totals['total_amount'],
                'note'         => $data['note'] ?? null,
            ]);

            $this->syncDetails($so, $data['items']);

            return $so;
        });

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('ok', "สร้างใบสั่งขาย {$salesOrder->code_so} เรียบร้อย");
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load([
            'customer',
            'quotation',
            'details.product',
            'camp.deliveryNotes',
        ]);

        return view('sales_orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canEdit()) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', 'ใบสั่งขายที่ยืนยันแล้วไม่สามารถแก้ไขได้');
        }

        $salesOrder->load(['customer', 'quotation', 'details.product']);

        return view('sales_orders.edit', [
            'salesOrder' => $salesOrder,
            'products'   => Product::orderBy('name_product')->get(),
            'customers'  => Customer::orderBy('name_customer')->get(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (! $salesOrder->canEdit()) {
            return redirect()
                ->route('sales-orders.show', $salesOrder)
                ->with('error', 'ใบสั่งขายที่ยืนยันแล้วไม่สามารถแก้ไขได้');
        }

        $data = $this->validateSalesOrder($request, $salesOrder);

        $totals = $this->calculateTotals($data['items'], $data['discount'] ?? 0);

        DB::transaction(function () use ($salesOrder, $data, $totals) {

            $salesOrder->update([
                'order_date'   => $data['order_date'],
                'due_date'     => $data['due_date'] ?? null,
                'po_number'    => $data['po_number'] ?? null,
                'subtotal'     => $totals['subtotal'],
                'discount'     => $totals['discount'],
                'vat_amount'   => $totals['vat_amount'],
                'total_amount' => $totals['total_amount'],
                'note'         => $data['note'] ?? null,
            ]);

            $salesOrder->details()->delete();
            $this->syncDetails($salesOrder, $data['items']);
        });

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('ok', 'แก้ไขใบสั่งขายเรียบร้อย');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->hasCamp()) {
            return back()->with('error', 'ใบสั่งขายนี้เปิดแคมป์ไปแล้ว ไม่สามารถลบได้');
        }

        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'ลบได้เฉพาะใบสั่งขายที่ยังเป็นร่าง หากต้องการยกเลิกให้ใช้ปุ่มยกเลิก');
        }

        DB::transaction(function () use ($salesOrder) {
            $salesOrder->details()->delete();
            $salesOrder->delete();
        });

        return redirect()
            ->route('sales-orders.index')
            ->with('ok', 'ลบใบสั่งขายแล้ว');
    }

    /* ==================== เปลี่ยนสถานะ ==================== */

    /** ยืนยันใบสั่งขาย = ล็อกรายการ พร้อมเปิดแคมป์ */
    public function confirm(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canConfirm()) {
            return back()->with('error', $salesOrder->details()->exists()
                ? 'ใบสั่งขายนี้ไม่อยู่ในสถานะร่าง'
                : 'ไม่มีรายการสินค้า ไม่สามารถยืนยันได้');
        }

        $salesOrder->update(['status' => 'confirmed']);

        return back()->with('ok', 'ยืนยันใบสั่งขายเรียบร้อย พร้อมเปิดแคมป์งานแล้ว');
    }

    /** ย้อนกลับเป็นร่าง เพื่อแก้ไข (ทำได้ก่อนเปิดแคมป์เท่านั้น) */
    public function revertToDraft(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'confirmed') {
            return back()->with('error', 'ย้อนกลับเป็นร่างได้เฉพาะใบที่ยืนยันแล้วเท่านั้น');
        }

        if ($salesOrder->hasCamp()) {
            return back()->with('error', 'เปิดแคมป์ไปแล้ว ไม่สามารถย้อนกลับเป็นร่างได้');
        }

        $salesOrder->update(['status' => 'draft']);

        return back()->with('ok', 'ย้อนกลับเป็นร่างแล้ว แก้ไขรายการได้');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canCancel()) {
            return back()->with('error', $salesOrder->hasCamp()
                ? 'ใบสั่งขายนี้เปิดแคมป์ไปแล้ว ไม่สามารถยกเลิกได้'
                : 'ไม่สามารถยกเลิกใบสั่งขายในสถานะนี้ได้');
        }

        $salesOrder->update(['status' => 'cancelled']);

        return back()->with('ok', 'ยกเลิกใบสั่งขายแล้ว');
    }

    /** ปิดงาน = ส่งของครบแล้ว */
    public function close(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'in_progress') {
            return back()->with('error', 'ปิดงานได้เฉพาะใบสั่งขายที่กำลังดำเนินการอยู่');
        }

        $salesOrder->update(['status' => 'closed']);

        return back()->with('ok', 'ปิดงานใบสั่งขายแล้ว');
    }

    /* ==================== PDF ==================== */

    public function pdf(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'quotation', 'details.product']);

        return Pdf::loadView('sales_orders.pdf', compact('salesOrder'))
            ->setPaper('A4', 'portrait')
            ->stream('sales-order-' . $salesOrder->code_so . '.pdf');
    }

    /* ==================== Helpers ==================== */

    private function validateSalesOrder(Request $request, ?SalesOrder $salesOrder = null): array
    {
        return $request->validate([
            'id_quot'            => ['nullable', 'exists:quotations,id_quot'],
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'order_date'         => ['required', 'date'],
            'due_date'           => ['nullable', 'date', 'after_or_equal:order_date'],
            'po_number'          => ['nullable', 'string', 'max:50'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id_product' => ['required', 'exists:products,id_product'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ], [
            'id_customer.required'   => 'กรุณาเลือกลูกค้า',
            'order_date.required'    => 'กรุณาระบุวันที่สั่งซื้อ',
            'due_date.after_or_equal' => 'กำหนดส่งมอบต้องไม่ก่อนวันที่สั่งซื้อ',
            'items.required'         => 'ต้องมีรายการอย่างน้อย 1 รายการ',
            'items.*.quantity.gt'    => 'จำนวนต้องมากกว่า 0',
        ]);
    }

    /** ใบเสนอราคาต้องอนุมัติแล้ว ยังไม่มีใบสั่งขาย และเป็นของลูกค้ารายเดียวกัน */
    private function checkQuotation(array $data): ?string
    {
        if (empty($data['id_quot'])) {
            return null;
        }

        $quotation = Quotation::find($data['id_quot']);

        if (! $quotation) {
            return 'ไม่พบใบเสนอราคาที่เลือก';
        }

        if ($quotation->status !== 'approved') {
            return 'ใบเสนอราคาที่เลือกยังไม่ได้รับการอนุมัติ';
        }

        if ($quotation->hasSalesOrder()) {
            return 'ใบเสนอราคานี้ออกใบสั่งขายไปแล้ว';
        }

        if ((int) $quotation->id_customer !== (int) $data['id_customer']) {
            return 'ใบเสนอราคาที่เลือกเป็นของลูกค้ารายอื่น กรุณาตรวจสอบอีกครั้ง';
        }

        return null;
    }

    /**
     * คำนวณยอดและเก็บเป็นคอลัมน์
     * แยก vat_amount ออกมาต่างหาก เพื่อใช้ออกใบกำกับภาษีได้ถูกต้อง
     */
    private function calculateTotals(array $items, mixed $discount): array
    {
        $subtotal = round(collect($items)->sum(
            fn(array $item) => (float) $item['quantity'] * (float) $item['price']
        ), 2);

        $discount      = min((float) $discount, $subtotal);
        $afterDiscount = round($subtotal - $discount, 2);
        $vatAmount     = round($afterDiscount * self::VAT_RATE, 2);

        return [
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'vat_amount'   => $vatAmount,
            'total_amount' => round($afterDiscount + $vatAmount, 2),
        ];
    }

    private function syncDetails(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $item) {
            SalesOrderDetail::create([
                'id_so'          => $salesOrder->id_so,
                'id_product'     => $item['id_product'],
                'quantity'       => $item['quantity'],
                'price_per_unit' => $item['price'],
                'total_price'    => round($item['quantity'] * $item['price'], 2),
            ]);
        }
    }
}