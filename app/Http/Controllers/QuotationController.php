<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Customer;
use App\Models\Product;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class QuotationController extends Controller
{
    /** อายุใบเสนอราคานับจากวันที่ออก (วัน) */
    private const VALID_DAYS = 30;

    /**
     * ตัวเลือกจำนวนรายการต่อหน้าที่ระบบยอมรับ
     *
     * ประกาศเป็นค่าคงที่เพื่อให้ Controller กับ Blade ใช้ชุดเดียวกัน
     * และกันไม่ให้ผู้ใช้ยัด ?per_page=999999 ผ่าน URL จนดึงข้อมูลทั้งตาราง
     */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /** จำนวนรายการต่อหน้าเริ่มต้น */
    public const DEFAULT_PER_PAGE = 10;

    /** สถานะที่ใช้กรองได้ในหน้ารายการ */
    public const FILTERABLE_STATUSES = ['draft', 'approved', 'rejected'];

    public function index(Request $request)
    {
        // ---------- จำนวนรายการต่อหน้า ----------
        // ต้องตรวจกับ whitelist เสมอ ห้ามเชื่อค่าจาก query string ตรง ๆ
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        // ---------- คำค้นหา ----------
        $search = trim((string) $request->input('search', ''));

        // ---------- ตัวกรองสถานะ ----------
        $status = $request->input('status');

        if (! in_array($status, self::FILTERABLE_STATUSES, true)) {
            $status = null;
        }

        $quotations = Quotation::with('customer')
            // ค้นหาจากเลขที่เอกสาร หรือชื่อลูกค้า
            // ครอบด้วย closure เพื่อให้ orWhere ไม่หลุดไปชนกับเงื่อนไข status
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('code_quot', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name_customer', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== null, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest('id_quot')
            ->paginate($perPage)
            // สำคัญ: พา search / status / per_page ติดไปกับลิงก์เปลี่ยนหน้าด้วย
            // ถ้าไม่ใส่ พอกดหน้า 2 แล้วเงื่อนไขค้นหาจะหายหมด
            ->withQueryString();

        return view('quotations.index', [
            'quotations'     => $quotations,
            'perPage'        => $perPage,
            'search'         => $search,
            'status'         => $status,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function create()
    {
        return view('quotations.create', [
            'customers' => Customer::all(),
            'products'  => Product::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->validateQuotation($request);

        $money = $this->calculateTotals($request->items, $request->discount);

        // ใช้ transaction เพื่อให้หัวเอกสารกับรายการสินค้าเกิดพร้อมกัน
        // ถ้ารายการใดรายการหนึ่งพัง จะไม่เหลือใบเสนอราคาที่ไม่มีรายการค้างในระบบ
        $quotation = DB::transaction(function () use ($request, $money) {

            $quotation = Quotation::create([
                'id_customer'       => $request->id_customer,
                'date_quot'         => now(),
                'end_quot'          => now()->addDays(self::VALID_DAYS),
                'status'            => 'draft',

                // บันทึกผู้จัดทำ ณ ตอนสร้าง
                // เก็บลงคอลัมน์แทนการอ่าน auth() ตอนพิมพ์ PDF
                // เพราะเอกสารต้องแสดงคนที่ทำจริงในวันนั้น ไม่ใช่คนที่กำลังเปิดดู
                'issued_by_user_id' => auth()->id(),
                'issued_at'         => now(),

                'subtotal'          => $money['subtotal'],
                'discount'          => $money['discount'],
                'vat_rate'          => $money['vat_rate'],
                'vat_amount'        => $money['vat_amount'],
                'total_amount'      => $money['total_amount'],
            ]);

            $this->syncDetails($quotation, $request->items);

            return $quotation;
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok', 'สร้างใบเสนอราคาเรียบร้อย');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('customer', 'details.product', 'salesOrder.camp', 'issuer', 'approver');
        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        // แก้ไขได้เฉพาะใบร่างเท่านั้น
        // ใบที่อนุมัติแล้วถือว่าส่งถึงลูกค้าแล้ว การแก้ย้อนหลังทำให้
        // เอกสารในมือลูกค้ากับในระบบไม่ตรงกัน
        if ($quotation->status !== 'draft') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'ใบเสนอราคานี้ไม่อยู่ในสถานะร่าง ไม่สามารถแก้ไขได้');
        }

        $quotation->load('details.product', 'customer');

        return view('quotations.edit', [
            'quotation' => $quotation,
            'customers' => Customer::all(),
            'products'  => Product::all(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        // ต้องเช็คซ้ำที่นี่ด้วย เพราะผู้ใช้อาจเปิดหน้าแก้ไขค้างไว้
        // แล้วมีคนอื่นกดอนุมัติระหว่างนั้น
        if ($quotation->status !== 'draft') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'ใบเสนอราคานี้ไม่อยู่ในสถานะร่าง ไม่สามารถแก้ไขได้');
        }

        $this->validateQuotation($request);

        $money = $this->calculateTotals($request->items, $request->discount);

        DB::transaction(function () use ($request, $quotation, $money) {

            $quotation->update([
                'id_customer'  => $request->id_customer,
                'subtotal'     => $money['subtotal'],
                'discount'     => $money['discount'],
                'vat_rate'     => $money['vat_rate'],
                'vat_amount'   => $money['vat_amount'],
                'total_amount' => $money['total_amount'],
            ]);

            $quotation->details()->delete();

            $this->syncDetails($quotation, $request->items);
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok', 'อัปเดตใบเสนอราคาเรียบร้อย');
    }

    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);

        // กันลบใบที่ออกใบสั่งขายไปแล้ว
        // ถ้าลบได้ ใบสั่งขายกับแคมป์จะเหลือชี้ไปหาเอกสารที่ไม่มีอยู่
        if ($quotation->hasSalesOrder()) {
            return back()->with('error', 'ใบเสนอราคานี้ออกใบสั่งขายไปแล้ว ไม่สามารถลบได้');
        }

        DB::transaction(function () use ($quotation) {
            $quotation->details()->delete();
            $quotation->delete();
        });

        return redirect()->route('quotations.index')
            ->with('ok', 'ลบใบเสนอราคาสำเร็จ');
    }

    public function approve($id)
    {
        $q = Quotation::findOrFail($id);

        if ($q->status !== 'draft') {
            return back()->with('error', 'ใบเสนอราคานี้ไม่อยู่ในสถานะร่าง ไม่สามารถอนุมัติได้');
        }

        if ($q->details()->count() === 0) {
            return back()->with('error', 'ไม่มีรายการสินค้า ไม่สามารถอนุมัติได้');
        }

        $q->status              = 'approved';
        $q->approved_by_user_id = auth()->id();   // บันทึกผู้อนุมัติ
        $q->approved_at         = now();
        $q->save();

        return back()->with('ok', 'อนุมัติใบเสนอราคาเรียบร้อย');
    }

    public function cancel($id)
    {
        $q = Quotation::findOrFail($id);

        if ($q->hasSalesOrder()) {
            return back()->with('error', 'ใบเสนอราคานี้ออกใบสั่งขายไปแล้ว ไม่สามารถยกเลิกได้');
        }

        if (! in_array($q->status, ['draft', 'approved'])) {
            return back()->with('error', 'ไม่สามารถยกเลิกได้');
        }

        $q->status = 'rejected';
        $q->save();

        return back()->with('ok', 'ยกเลิกใบเสนอราคาแล้ว');
    }

    public function downloadPDF(Quotation $quotation)
    {
        // ต้อง load issuer กับ approver ด้วย ไม่งั้นลายเซ็นจะไม่ขึ้นใน PDF
        $quotation->load('customer', 'details.product', 'issuer', 'approver');

        $pdf = Pdf::loadView('quotations.pdf', compact('quotation'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('quotation.pdf');
    }

    /* ==================== Private helpers ==================== */

    private function validateQuotation(Request $request): void
    {
        $request->validate([
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id_product' => ['required', 'exists:products,id_product'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * คำนวณยอดเงินทั้งหมด คืนเป็น array พร้อมแยก VAT
     *
     * เก็บ vat_rate ลงเอกสารด้วย เพื่อให้เอกสารเก่าพิมพ์ซ้ำได้ถูกต้อง
     * แม้อัตรา VAT จะเปลี่ยนในอนาคต
     */
    private function calculateTotals(array $items, mixed $discount): array
    {
        $subtotal = round(collect($items)->sum(
            fn (array $item) => (float) $item['quantity'] * (float) $item['price']
        ), 2);

        // ส่วนลดห้ามเกินยอดรวม
        $discount      = min((float) $discount, $subtotal);
        $afterDiscount = round($subtotal - $discount, 2);

        $vatRate   = Quotation::DEFAULT_VAT_RATE;
        $vatAmount = round($afterDiscount * ($vatRate / 100), 2);

        return [
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'vat_rate'      => $vatRate,
            'vat_amount'    => $vatAmount,
            'total_amount'  => round($afterDiscount + $vatAmount, 2),
        ];
    }

    /** สร้างรายการสินค้าของใบเสนอราคา */
    private function syncDetails(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            QuotationDetail::create([
                'id_quot'        => $quotation->id_quot,
                'id_product'     => $item['id_product'],
                'quantity'       => $item['quantity'],
                'price_per_unit' => $item['price'],
                'total_price'    => $item['quantity'] * $item['price'],
            ]);
        }
    }
}