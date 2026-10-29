<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class QuotationController extends Controller
{
    /** อายุใบเสนอราคานับจากวันที่ออก (วัน) */
    private const VALID_DAYS = 30;

    /** สถานะที่ใช้กรองได้ในหน้ารายการ */
    public const FILTERABLE_STATUSES = ['draft', 'approved', 'rejected'];

    public function index(Request $request)
    {
        $perPage =$this->perPage($request);$search  = $this->searchTerm($request);
        [$dateFrom,$dateTo] = $this->dateRange($request);

        $statuses = $this->statusesMatching($search, Quotation::STATUS_LABELS);
        $status =$this->statusFilter($request, self::FILTERABLE_STATUSES);$ranges = $this->numberRanges($request, [
            'total' => ['label' => 'ยอดสุทธิ', 'unit' => 'บาท', 'step' => '0.01'],
        ]);

        $quotations = Quotation::with('customer')
            ->when($search !== '', function ($query) use ($search,$statuses) {
                $query->where(function ($sub) use ($search,$statuses) {
                    $sub->where('code_quot', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name_customer', 'like', "%{$search}%");
                        });
                    if ($statuses) {
                        $sub->orWhereIn('status',$statuses);
                    }
                });
            })
            ->when($status, fn ($query) => $query->where('status',$status))
            ->tap(fn ($query) =>$this->applyNumberRange($query, 'total_amount',$ranges['total']))
            ->tap(fn ($query) => $this->applyDateRange($query, 'date_quot', $dateFrom,$dateTo))
            ->latest('id_quot')
            ->paginate($perPage)
            ->withQueryString();

        // หน้าต่างสร้างใบเสนอราคา (เปิดจากปุ่มในหน้านี้)
        $customers = Customer::orderBy('name_customer')->get();
        $products  = Product::orderBy('name_product')->get();

        return view('quotations.index', compact(
            'quotations', 'perPage', 'search', 'dateFrom', 'dateTo', 'status', 'ranges', 'customers', 'products'
        ));
    }

    /** สร้างใบเสนอราคาในหน้าต่าง (modal) ของหน้ารายการ */
    public function create()
    {
        return redirect()->route('quotations.index', ['modal' => 'create']);
    }

    public function store(Request $request)
    {
        $this->validateQuotation($request, 'createQuotation');
        $money = $this->calculateTotals($request->items);

        $quotation = DB::transaction(function () use ($request, $money) {$quotation = Quotation::create([
                'id_customer'       => $request->id_customer,
                'date_quot'         => now(),
                'end_quot'          => now()->addDays(self::VALID_DAYS),
                'status'            => 'draft',
                'issued_by_user_id' => auth()->id(),
                'issued_at'         => now(),
                'subtotal'          => $money['subtotal'],
                'discount'          => $money['discount'],
                'vat_rate'          => $money['vat_rate'],
                'vat_amount'        => $money['vat_amount'],
                'total_amount'      => $money['total_amount'],
            ]);

            $this->syncDetails($quotation,$request->items);

            return $quotation;
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok', 'สร้างใบเสนอราคาเรียบร้อย');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('customer', 'details.product', 'salesOrder.camp', 'issuer', 'approver');

        // หน้าต่างแก้ไข (เฉพาะใบร่าง)
        $customers = $quotation->status === 'draft' ? Customer::orderBy('name_customer')->get() : collect();
        $products  = $quotation->status === 'draft' ? Product::orderBy('name_product')->get() : collect();

        return view('quotations.show', compact('quotation', 'customers', 'products'));
    }

    /** แก้ไขใบเสนอราคาในหน้าต่าง (modal) ของหน้าดูใบเสนอราคา */
    public function edit(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'ใบเสนอราคานี้ไม่อยู่ในสถานะร่าง ไม่สามารถแก้ไขได้');
        }

        return redirect()->route('quotations.show', [$quotation, 'edit' => 1]);
    }

    public function update(Request $request, Quotation$quotation)
    {
        if ($quotation->status !== 'draft') {
            return redirect()
                ->route('quotations.show', $quotation)
                ->with('error', 'ใบเสนอราคานี้ไม่อยู่ในสถานะร่าง ไม่สามารถแก้ไขได้');
        }

        $this->validateQuotation($request, 'updateQuotation');
        $money = $this->calculateTotals($request->items);

        DB::transaction(function () use ($request,$quotation, $money) {$quotation->update([
                'id_customer'  => $request->id_customer,
                'subtotal'     => $money['subtotal'],
                'discount'     => $money['discount'],
                'vat_rate'     => $money['vat_rate'],
                'vat_amount'   => $money['vat_amount'],
                'total_amount' => $money['total_amount'],
            ]);

            $quotation->details()->delete();$this->syncDetails($quotation,$request->items);
        });

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok', 'อัปเดตใบเสนอราคาเรียบร้อย');
    }

    public function approve($id)
    {
        $q = Quotation::findOrFail($id);

        // ผู้อนุมัติต้องเป็น admin และไม่ใช่ผู้จัดทำใบนี้ (ดูกฎใน Quotation::approveBlocker)
        if ($reason = $q->approveBlocker(auth()->user())) {
            return back()->with('error', 'อนุมัติไม่ได้: ' . $reason);
        }

        if ($q->details()->count() === 0) {
            return back()->with('error', 'ไม่มีรายการสินค้า ไม่สามารถอนุมัติได้');
        }

        $q->status              = 'approved';
        $q->approved_by_user_id = auth()->id();
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

        return back()->with('ok', 'ยกเลิกใบเสนอราคาแล้ว (เอกสารยังอยู่ในระบบ เลขที่ไม่หาย)');
    }

    public function downloadPDF(Quotation $quotation)
    {
        // โหลดข้อมูลพนักงานผู้ออก/อนุมัติ + ข้อมูลบริษัทตั้งต้น
        $quotation->load('customer', 'details.product', 'issuer', 'approver');$settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('quotations.pdf', compact('quotation', 'settings'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('quotation.pdf');
    }

    /* ==================== Private helpers ==================== */

    private function validateQuotation(Request $request, string $bag): void
    {
        $request->validateWithBag($bag, [
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id_product' => ['required', 'exists:products,id_product'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'items.*.price'      => ['required', 'numeric', 'min:0'],
        ], [
            'id_customer.required'        => 'กรุณาเลือกลูกค้า',
            'id_customer.exists'          => 'ไม่พบลูกค้าที่เลือก',
            'items.required'              => 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ',
            'items.*.id_product.required' => 'กรุณาเลือกสินค้าให้ครบทุกรายการ',
            'items.*.quantity.required'   => 'กรุณาใส่จำนวนให้ครบทุกรายการ',
            'items.*.quantity.gt'         => 'จำนวนต้องมากกว่า 0',
        ]);
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = round(collect($items)->sum(
            fn (array $item) => (float) $item['quantity'] * (float)$item['price']
        ), 2);

        $discount      = 0;
        $afterDiscount =$subtotal;
        $vatRate       = Quotation::DEFAULT_VAT_RATE;
        
        // แก้ไขไวยากรณ์การคำนวณ VAT
        $vatAmount     = round($afterDiscount * ($vatRate / 100), 2);

        return [
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'vat_rate'     => $vatRate,
            'vat_amount'   => $vatAmount,
            'total_amount' => round($afterDiscount +$vatAmount, 2),
        ];
    }

    private function syncDetails(Quotation $quotation, array$items): void
    {
        foreach ($items as$item) {
            QuotationDetail::create([
                'id_quot'        => $quotation->id_quot,
                'id_product'     => $item['id_product'],
                'quantity'       => $item['quantity'],
                'price_per_unit' => $item['price'],
                'total_price'    => round((float) $item['quantity'] * (float) $item['price'], 2),
            ]);
        }
    }
}