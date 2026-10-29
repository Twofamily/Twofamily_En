<?php

namespace App\Http\Controllers;

use App\Models\Camp;
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
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        [$dateFrom, $dateTo] = $this->dateRange($request);

        // พิมพ์ "ยืนยัน" / "ปิดงาน" / "ยกเลิก" ก็ค้นตามสถานะได้
        $statuses = $this->statusesMatching($search, SalesOrder::STATUS_LABELS);

        // dropdown เลือกสถานะ + ช่วงยอดสุทธิ
        $status = $this->statusFilter($request, array_keys(SalesOrder::STATUS_LABELS));
        $ranges = $this->numberRanges($request, [
            'total' => ['label' => 'ยอดสุทธิ', 'unit' => 'บาท', 'step' => '0.01'],
        ]);

        $salesOrders = SalesOrder::with(['customer', 'quotation', 'camp'])
            // ช่องค้นหาช่องเดียว: เลขที่ใบสั่งขาย เลข PO ลูกค้า ใบเสนอราคา แคมป์ และสถานะ
            // ครอบด้วย closure เพื่อให้ orWhere ไม่หลุดไปชนกับช่วงวันที่
            ->when($search !== '', function ($query) use ($search, $statuses) {
                $query->where(function ($sub) use ($search, $statuses) {
                    $sub->where('code_so', 'like', "%{$search}%")
                        ->orWhere('po_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($q) => $q->where('code_quot', 'like', "%{$search}%"))
                        ->orWhereHas('camp', fn ($c) => $c->where('code_camp', 'like', "%{$search}%")
                                                          ->orWhere('name_camp', 'like', "%{$search}%"));
                    if ($statuses) {
                        $sub->orWhereIn('status', $statuses);
                    }
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'total_amount', $ranges['total']))
            ->tap(fn ($query) => $this->applyDateRange($query, 'order_date', $dateFrom, $dateTo))
            ->latest('id_so')
            ->paginate($perPage)
            // พา search / ช่วงวันที่ / per_page ติดไปกับลิงก์เปลี่ยนหน้า
            ->withQueryString();

        return view('sales_orders.index', compact(
            'salesOrders', 'perPage', 'search', 'dateFrom', 'dateTo', 'status', 'ranges'
        ) + $this->createFormData($request));
    }

    /**
     * ข้อมูลของหน้าต่างสร้างใบสั่งขาย (อยู่ในหน้ารายการ)
     *   ?quotation=ID : จากใบเสนอราคาที่อนุมัติแล้ว (ราคาตามใบเสนอราคา)
     *   ?direct=1     : สั่งตรง ไม่มีใบเสนอราคา (ลูกค้าประจำโทรสั่ง) — ต้องให้ผู้ดูแลระบบยืนยันราคา
     *   ไม่ส่งอะไรมา  : เลือกว่าจะสร้างแบบไหน
     */
    private function createFormData(Request $request): array
    {
        if (! $request->user()?->canEdit('sales_orders')) {
            return ['canCreate' => false];
        }

        $quotationId = $request->input('quotation') ?: old('id_quot');
        $quotation = $quotationId
            ? Quotation::with(['customer', 'details.product'])->find($quotationId)
            : null;

        // ใบเสนอราคาที่ออกใบสั่งขายไม่ได้แล้ว → กลับไปหน้าเลือก
        if ($quotation && ! $quotation->canCreateSalesOrder()) {
            $quotation = null;
        }

        $direct = ! $quotation && ($request->boolean('direct') || old('direct'));

        return [
            'canCreate'  => true,
            'quotation'  => $quotation,
            'direct'     => $direct,
            'quotations' => Quotation::with('customer')->convertible()->latest('id_quot')->get(),
            'customers'  => $direct ? Customer::orderBy('name_customer')->get() : collect(),
            'products'   => Product::orderBy('name_product')->get(),
        ];
    }

    /** สร้างใบสั่งขายในหน้าต่าง (modal) ของหน้ารายการ — ลิงก์เดิม (?quotation= / ?direct=) ยังใช้ได้ */
    public function create(Request $request)
    {
        $quotation = $request->quotation ? Quotation::find($request->quotation) : null;

        // กันการเปิดใบสั่งขายซ้ำจากใบเสนอราคาเดิม
        if ($quotation && ! $quotation->canCreateSalesOrder()) {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', $quotation->hasSalesOrder()
                    ? 'ใบเสนอราคานี้ออกใบสั่งขายไปแล้ว'
                    : 'ใบเสนอราคานี้ยังไม่ได้รับการอนุมัติ');
        }

        return redirect()->route('sales_orders.index', array_filter([
            'modal'     => 'create',
            'quotation' => $quotation?->id_quot,
            'direct'    => $request->boolean('direct') ? 1 : null,
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validateSalesOrder($request, 'createSalesOrder');

        if ($error = $this->checkQuotation($data)) {
            return back()->withInput()->withErrors(['id_quot' => $error], 'createSalesOrder');
        }

        $totals = $this->calculateTotals($data['items']);

        $salesOrder = DB::transaction(function () use ($data, $totals) {

            $so = SalesOrder::create([
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
            ->route('sales_orders.show', $salesOrder)
            ->with('ok', $salesOrder->isDirect()
                ? "สร้างใบสั่งตรง {$salesOrder->code_so} เรียบร้อย — รอผู้ดูแลระบบตรวจราคาและยืนยัน"
                : "สร้างใบสั่งขาย {$salesOrder->code_so} เรียบร้อย");
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load([
            'customer',
            'quotation',
            'details.product',
            'camp.deliveryNotes',
        ]);

        // แคมป์ที่ยังเปิดอยู่ของลูกค้ารายนี้ ให้เลือก "เพิ่มเข้าแคมป์เดิม" ได้ (กรณีสั่งเพิ่ม)
        $campOptions = $salesOrder->canOpenCamp()
            ? Camp::open()
                ->where('id_customer', $salesOrder->id_customer)
                ->latest('id_camp')
                ->get()
            : collect();

        // หน้าต่างแก้ไข (เฉพาะใบร่าง)
        $products = $salesOrder->canEdit() ? Product::orderBy('name_product')->get() : collect();

        return view('sales_orders.show', compact('salesOrder', 'campOptions', 'products'));
    }

    /** แก้ไขใบสั่งขายในหน้าต่าง (modal) ของหน้าดูใบสั่งขาย */
    public function edit(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canEdit()) {
            return redirect()
                ->route('sales_orders.show', $salesOrder)
                ->with('error', 'ใบสั่งขายที่ยืนยันแล้วไม่สามารถแก้ไขได้');
        }

        return redirect()->route('sales_orders.show', [$salesOrder, 'edit' => 1]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if (! $salesOrder->canEdit()) {
            return redirect()
                ->route('sales_orders.show', $salesOrder)
                ->with('error', 'ใบสั่งขายที่ยืนยันแล้วไม่สามารถแก้ไขได้');
        }

        $data = $this->validateSalesOrder($request, 'updateSalesOrder');

        $totals = $this->calculateTotals($data['items']);

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
            ->route('sales_orders.show', $salesOrder)
            ->with('ok', 'แก้ไขใบสั่งขายเรียบร้อย');
    }

    /* ==================== เปลี่ยนสถานะ ==================== */

    /** ยืนยันใบสั่งขาย = ล็อกรายการ พร้อมเปิดแคมป์ */
    public function confirm(SalesOrder $salesOrder)
    {
        if ($reason = $salesOrder->confirmBlocker(auth()->user())) {
            return back()->with('error', $reason);
        }

        $salesOrder->update(['status' => 'confirmed']);

        return back()->with('ok', 'ยืนยันใบสั่งขายเรียบร้อย — เปิดแคมป์ใหม่ หรือเพิ่มเข้าแคมป์เดิมของลูกค้าได้');
    }

    /**
     * ผูกใบสั่งขายเข้าแคมป์ที่มีอยู่แล้ว (ลูกค้าสั่งเพิ่มในงานเดิม)
     * กรณีเปิดแคมป์ใหม่ ไปที่ camps.create?sales_order=... แทน
     */
    public function attachCamp(Request $request, SalesOrder $salesOrder)
    {
        if (! $salesOrder->canOpenCamp()) {
            return back()->with('error', $salesOrder->hasCamp()
                ? 'ใบสั่งขายนี้อยู่ในแคมป์แล้ว'
                : 'ต้องยืนยันใบสั่งขายก่อน จึงจะเพิ่มเข้าแคมป์ได้');
        }

        $data = $request->validate([
            'id_camp' => ['required', 'exists:camps,id_camp'],
        ], [
            'id_camp.required' => 'กรุณาเลือกแคมป์',
            'id_camp.exists'   => 'ไม่พบแคมป์ที่เลือก',
        ]);

        $camp = Camp::findOrFail($data['id_camp']);

        if ((int) $camp->id_customer !== (int) $salesOrder->id_customer) {
            return back()->with('error', 'แคมป์ที่เลือกเป็นของลูกค้ารายอื่น');
        }

        if (! $camp->isOpen()) {
            return back()->with('error', 'แคมป์ที่เลือกปิดไปแล้ว');
        }

        $salesOrder->update([
            'id_camp' => $camp->id_camp,
            'status'  => 'in_progress',
        ]);

        return redirect()
            ->route('camps.show', $camp)
            ->with('ok', "เพิ่มใบสั่งขาย {$salesOrder->code_so} เข้าแคมป์ {$camp->code_camp} แล้ว");
    }

    /** ย้อนกลับเป็นร่าง เพื่อแก้ไข (ทำได้ก่อนเปิดแคมป์เท่านั้น) */
    public function revertToDraft(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'confirmed') {
            return back()->with('error', 'ย้อนกลับเป็นร่างได้เฉพาะใบที่ยืนยันแล้วเท่านั้น');
        }

        if ($salesOrder->hasCamp()) {
            return back()->with('error', 'ใบสั่งขายนี้อยู่ในแคมป์แล้ว ไม่สามารถย้อนกลับเป็นร่างได้');
        }

        $salesOrder->update(['status' => 'draft']);

        return back()->with('ok', 'ย้อนกลับเป็นร่างแล้ว แก้ไขรายการได้');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        if (! $salesOrder->canCancel()) {
            return back()->with('error', $salesOrder->hasCamp()
                ? 'ใบสั่งขายนี้อยู่ในแคมป์แล้ว ไม่สามารถยกเลิกได้'
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

    private function validateSalesOrder(Request $request, string $bag): array
    {
        return $request->validateWithBag($bag, [
            'id_quot'            => ['nullable', 'exists:quotations,id_quot'],
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'order_date'         => ['required', 'date'],
            'due_date'           => ['nullable', 'date', 'after_or_equal:order_date'],
            'po_number'          => ['nullable', 'string', 'max:50'],
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
    private function calculateTotals(array $items): array
    {
        $subtotal = round(collect($items)->sum(
            fn(array $item) => (float) $item['quantity'] * (float) $item['price']
        ), 2);

        // บริษัทไม่มีส่วนลดสินค้าแล้ว — คอลัมน์ discount ยังอยู่ในตาราง แต่บันทึกเป็น 0 เสมอ
        $discount      = 0;
        $afterDiscount = $subtotal;
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