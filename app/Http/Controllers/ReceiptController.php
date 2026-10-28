<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ใบเสร็จรับเงิน 3 แบบ
 *
 *   invoice : createFromInvoice() — ปุ่มในหน้าใบแจ้งหนี้ (แบบเดิม)
 *   advance : create/store ?type=advance — รับเงินล่วงหน้า ผูกแคมป์
 *   direct  : create/store ?type=direct  — ขายสด ไม่ต้องมีแคมป์
 */
class ReceiptController extends Controller
{
    /** ความสัมพันธ์ที่หน้าแสดงผลและ PDF ใช้ */
    private const DISPLAY_RELATIONS = [
        'customer',
        'camp',
        'issuer',
        'items.product',
        'payments.recordedBy',
        'appliedAdvances.invoice',
        'invoice.customer',
        'invoice.details.product',
        'invoice.quotation',
        'invoice.deliveryNotes',
        'invoice.payments.recordedBy',
        'invoice.payments.advanceReceipt',
    ];

    /* ==================== แบบที่ 1 : ชำระตามใบแจ้งหนี้ ==================== */

    public function createFromInvoice($id)
    {
        $invoice = Invoice::with(['receipt', 'payments', 'details'])->findOrFail($id);

        // ออกไปแล้ว → พาไปดูใบเดิม ไม่สร้างซ้ำ
        if ($invoice->hasReceipt()) {
            return redirect()
                ->route('receipts.show', $invoice->receipt->id_receipt)
                ->with('warning', 'ใบแจ้งหนี้นี้ออกใบเสร็จไปแล้ว');
        }

        // หักเงินล่วงหน้าครบทั้งใบ → ใบเสร็จรับเงินล่วงหน้าคือใบเสร็จของเงินก้อนนี้แล้ว
        if ($invoice->isSettledByAdvance()) {
            return redirect()
                ->route('invoices.show', $invoice->id_invoice)
                ->with('warning', 'ใบแจ้งหนี้นี้ชำระครบด้วยเงินรับล่วงหน้าแล้ว ไม่ต้องออกใบเสร็จใหม่');
        }

        // ด่านหลัก: ต้องมีหลักฐานการชำระครบยอด
        if (! $invoice->canIssueReceipt()) {
            return redirect()
                ->route('invoices.show', $invoice->id_invoice)
                ->with('error', 'ยังแนบหลักฐานการชำระไม่ครบยอด (คงเหลือ '
                    . number_format($invoice->remainingAmount(), 2)
                    . ' บาท) จึงออกใบเสร็จไม่ได้');
        }

        // ใช้ยอดที่บันทึกไว้ในใบแจ้งหนี้ ไม่คำนวณใหม่จาก quotation
        // ยอดบนใบเสร็จ = เงินที่จ่ายจริงรอบนี้ (ส่วนที่หักเงินล่วงหน้า ออกใบเสร็จไปแล้วตอนรับเงิน)
        $receipt = Receipt::create([
            'receipt_type'      => Receipt::TYPE_INVOICE,
            'id_invoice'        => $invoice->id_invoice,
            'id_customer'       => $invoice->id_customer,
            'id_camp'           => $invoice->id_camp,
            'subtotal'          => $invoice->itemsSubtotal(),
            'vat_amount'        => $invoice->itemsVat(),
            'advance_deducted'  => $invoice->advanceAppliedAmount(),
            'total'             => $invoice->cashPaidAmount(),
            'date_receipt'      => now(),
            'issued_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('receipts.show', $receipt->id_receipt)
            ->with('success', 'ออกใบเสร็จ ' . $receipt->code_rc . ' เรียบร้อย');
    }

    /* ==================== แบบที่ 2-3 : รับเงินล่วงหน้า / ขายสด ==================== */

    public function create(Request $request)
    {
        $type = $request->input('type') === Receipt::TYPE_DIRECT
            ? Receipt::TYPE_DIRECT
            : Receipt::TYPE_ADVANCE;

        // ขายสดเท่านั้น: เตรียมลูกค้าทั่วไปไว้ให้กดเลือกได้ทันที
        // (ต้องสร้างก่อนดึงรายชื่อลูกค้า ไม่งั้นครั้งแรกจะไม่อยู่ใน dropdown)
        $walkIn = $type === Receipt::TYPE_DIRECT ? Customer::walkIn() : null;

        // รับเงินล่วงหน้าได้ทุกแคมป์ที่ยังไม่ปิด (ลูกค้ามักจ่ายมัดจำตั้งแต่ตอนเตรียมงาน)
        $camps = Camp::with('customer')
            ->open()
            ->latest('id_camp')
            ->get();

        return view('receipts.create', [
            'type'         => $type,
            'camps'        => $camps,
            'selectedCamp' => $request->input('camp'),
            'customers'    => Customer::orderBy('name_customer')->get(),
            'walkIn'       => $walkIn,
            'products'     => Product::orderBy('name_product')->get(),
            'requireSlip'  => (bool) Setting::get('payment.require_slip', true),   // ยกเว้นเงินสด (ดู Payment::slipRules)
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('receipt_type');
        abort_unless(in_array($type, [Receipt::TYPE_ADVANCE, Receipt::TYPE_DIRECT], true), 422);

        $rules = [
            'receipt_type' => 'required|in:advance,direct',
            'date_receipt' => 'required|date|before_or_equal:today',
            'note'         => 'nullable|string|max:1000',

            // หลักฐานการชำระ (ใช้ร่วมกันทั้งสองแบบ)
            'method'       => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'bank_name'    => 'nullable|required_if:method,transfer|string|max:100',
            'reference_no' => 'nullable|string|max:50',
            'cash_received' => 'nullable|numeric|min:0',   // เงินสดที่ลูกค้ายื่นมา (ไว้คิดเงินทอน)
            'slip'         => Payment::slipRules(),   // บังคับ ยกเว้นเงินสด
        ];

        if ($type === Receipt::TYPE_ADVANCE) {
            $rules += [
                'id_camp'     => 'required|exists:camps,id_camp',
                'amount'      => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ];
        } else {
            $rules += [
                'id_customer'        => 'required|exists:customers,id_customer',
                'id_camp'            => 'nullable|exists:camps,id_camp',
                'items'              => 'required|array|min:1',
                'items.*.id_product' => 'required|exists:products,id_product',
                'items.*.quantity'   => 'required|numeric|gt:0',
                'items.*.price'      => 'required|numeric|min:0',
            ];
        }

        $data = $request->validate($rules, [
            'date_receipt.required'        => 'กรุณาระบุวันที่รับเงิน',
            'date_receipt.before_or_equal' => 'วันที่รับเงินต้องไม่เกินวันนี้',
            'id_camp.required'             => 'กรุณาเลือกแคมป์',
            'id_customer.required'         => 'กรุณาเลือกลูกค้า',
            'amount.required'              => 'กรุณาระบุยอดเงินที่รับ',
            'amount.min'                   => 'ยอดเงินต้องมากกว่า 0',
            'items.required'               => 'ต้องมีรายการสินค้าอย่างน้อย 1 รายการ',
            'items.*.quantity.gt'          => 'จำนวนต้องมากกว่า 0',
            'method.required'              => 'กรุณาเลือกวิธีชำระ',
            'bank_name.required_if'        => 'กรุณาระบุธนาคาร เมื่อชำระด้วยการโอนเงิน',
            'slip.required_unless'         => 'กรุณาแนบหลักฐานการชำระเงิน (สลิปโอน / รูปเช็ค)',
            'slip.mimes'                   => 'หลักฐานต้องเป็นไฟล์ JPG, PNG หรือ PDF',
            'slip.max'                     => 'ไฟล์หลักฐานต้องมีขนาดไม่เกิน 5 MB',
        ]);

        /* ---------- ตรวจแคมป์ ---------- */
        $camp = ! empty($data['id_camp']) ? Camp::find($data['id_camp']) : null;

        if ($type === Receipt::TYPE_ADVANCE && ! $camp?->isOpen()) {
            return back()->withInput()->with('error', 'รับเงินล่วงหน้าได้เฉพาะแคมป์ที่ยังเปิดอยู่');
        }

        if ($type === Receipt::TYPE_DIRECT && $camp
            && (int) $camp->id_customer !== (int) $data['id_customer']) {
            return back()->withInput()->with('error', 'แคมป์ที่เลือกเป็นของลูกค้ารายอื่น');
        }

        /* ---------- คำนวณยอด (เก็บเป็นคอลัมน์) ---------- */
        if ($type === Receipt::TYPE_ADVANCE) {
            // ลูกค้าโอนมาเป็นยอดรวม → แยก VAT ออก
            $total = round((float) $data['amount'], 2);
            [$subtotal, $vat] = Receipt::splitGross($total);

            $lines = [[
                'id_product'  => null,
                'description' => 'รับเงินล่วงหน้า ' . $camp->code_camp . ' ' . $camp->name_camp
                                 . (! empty($data['description']) ? ' — ' . $data['description'] : ''),
                'quantity'    => 1,
                'price'       => $subtotal,
                'total'       => $subtotal,
            ]];

            $idCustomer = $camp->id_customer;
        } else {
            $lines = collect($data['items'])->map(fn ($item) => [
                'id_product'  => $item['id_product'],
                'description' => null,
                'quantity'    => (float) $item['quantity'],
                'price'       => (float) $item['price'],
                'total'       => round((float) $item['quantity'] * (float) $item['price'], 2),
            ])->all();

            $subtotal = round(collect($lines)->sum('total'), 2);
            $vat      = round($subtotal * Invoice::VAT_RATE / 100, 2);
            $total    = round($subtotal + $vat, 2);

            $idCustomer = $data['id_customer'];
        }

        /* ---------- เงินสด: รับมาเท่าไร ทอนเท่าไร ---------- */
        $cashNote = null;

        if ($data['method'] === 'cash' && isset($data['cash_received']) && $data['cash_received'] !== '') {
            $got = round((float) $data['cash_received'], 2);

            if ($got < $total) {
                return back()->withInput()->with('error', 'รับเงินมา ' . number_format($got, 2)
                    . ' บาท น้อยกว่ายอดรับชำระ ' . number_format($total, 2) . ' บาท');
            }

            $cashNote = 'รับเงินสด ' . number_format($got, 2) . ' บาท ทอน ' . number_format($got - $total, 2) . ' บาท';
        }

        /* ---------- ไฟล์หลักฐาน ---------- */
        $file = $request->file('slip');
        $path = $hash = null;

        if ($file) {
            $hash = hash_file('sha256', $file->getRealPath());

            $dup = Payment::with(['invoice', 'receipt'])->where('slip_hash', $hash)->first();
            if ($dup) {
                return back()->withInput()->with(
                    'error',
                    'ไฟล์หลักฐานนี้เคยถูกใช้แล้วในเอกสาร ' . $dup->ownerCode()
                );
            }

            $path = $file->store(Payment::SLIP_DIR, 'local');
        }

        try {
            $receipt = DB::transaction(function () use (
                $type, $data, $camp, $idCustomer, $subtotal, $vat, $total, $lines, $file, $path, $hash, $cashNote
            ) {
                $receipt = Receipt::create([
                    'receipt_type'      => $type,
                    'id_customer'       => $idCustomer,
                    'id_camp'           => $camp?->id_camp,
                    'subtotal'          => $subtotal,
                    'vat_amount'        => $vat,
                    'total'             => $total,
                    'date_receipt'      => $data['date_receipt'],
                    'description'       => $data['description'] ?? null,
                    'note'              => $data['note'] ?? null,
                    'issued_by_user_id' => auth()->id(),
                ]);

                foreach ($lines as $line) {
                    $receipt->items()->create($line);
                }

                $receipt->payments()->create([
                    'paid_at'             => $data['date_receipt'],
                    'amount'              => $total,
                    'method'              => $data['method'],
                    'bank_name'           => $data['bank_name'] ?? null,
                    'reference_no'        => $data['reference_no'] ?? null,
                    'note'                => $cashNote,
                    'slip_path'           => $path,
                    'slip_original_name'  => $file?->getClientOriginalName(),
                    'slip_hash'           => $hash,
                    'recorded_by_user_id' => auth()->id(),
                ]);

                return $receipt;
            });
        } catch (\Throwable $e) {
            // บันทึก DB ไม่สำเร็จ → ลบไฟล์ที่อัปไปแล้ว ไม่ให้มีไฟล์กำพร้า
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return redirect()
            ->route('receipts.show', $receipt->id_receipt)
            ->with('success', 'ออกใบเสร็จ' . $receipt->type_label . ' ' . $receipt->code_rc . ' เรียบร้อย');
    }

    /* ==================== แสดงผล ==================== */

    public function show($id)
    {
        $receipt = Receipt::with([...self::DISPLAY_RELATIONS, 'cancelledBy'])->withTrashed()->findOrFail($id);

        return view('receipts.show', compact('receipt'));
    }

    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        [$dateFrom, $dateTo] = $this->dateRange($request);

        // พิมพ์ "ล่วงหน้า" / "ขายสด" ก็ค้นตามประเภทได้
        $types = $this->statusesMatching($search, Receipt::TYPES);
        // "ยกเลิกแล้ว" อยู่ใน dropdown เดียวกับประเภท
        $type  = $this->statusFilter($request, [...array_keys(Receipt::TYPES), 'cancelled'], 'type');

        // ช่วงยอดรับชำระ (ต่ำสุด–สูงสุด)
        $ranges = $this->numberRanges($request, [
            'total' => ['label' => 'ยอดรับชำระ', 'unit' => 'บาท', 'step' => '0.01'],
        ]);

        $query = Receipt::with(['customer', 'invoice', 'camp', 'cancelledBy', 'appliedAdvances'])
            ->cancelledFilter($type === 'cancelled' ? 'cancelled' : null)
            // ช่องค้นหาช่องเดียว: เลขที่ใบเสร็จ ใบแจ้งหนี้ แคมป์ ชื่อลูกค้า และประเภท
            ->when($search !== '', function ($query) use ($search, $types) {
                $query->where(function ($sub) use ($search, $types) {
                    $sub->where('code_rc', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($inv) => $inv->where('code_inv', 'like', "%{$search}%"))
                        ->orWhereHas('camp', fn ($c) => $c->where('code_camp', 'like', "%{$search}%")
                                                         ->orWhere('name_camp', 'like', "%{$search}%"));
                    if ($types) {
                        $sub->orWhereIn('receipt_type', $types);
                    }
                });
            })
            ->ofType($type === 'cancelled' ? null : $type)
            ->tap(fn ($query) => $this->applyNumberRange($query, 'total', $ranges['total']))
            ->tap(fn ($query) => $this->applyDateRange($query, 'date_receipt', $dateFrom, $dateTo));

        // ยอดรวมรับชำระตามเงื่อนไข (ทุกหน้า ไม่ใช่เฉพาะหน้าที่แสดง)
        // ใบที่ยกเลิกแล้วไม่นับเป็นยอดรับชำระ
        $sumTotal = (clone $query)->whereNull('receipts.cancelled_at')->sum('total');

        $receipts = $query
            ->orderByDesc('id_receipt')
            ->paginate($perPage)
            ->withQueryString();

        return view('receipts.index', compact(
            'receipts', 'perPage', 'search', 'dateFrom', 'dateTo', 'sumTotal', 'ranges', 'type'
        ));
    }

    public function pdf($id)
    {
        $receipt = Receipt::with(self::DISPLAY_RELATIONS)->withTrashed()->findOrFail($id);

        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('receipts.pdf', compact('receipt', 'settings'));

        return $pdf->stream(($receipt->code_rc ?: 'RC-' . $receipt->id_receipt) . '.pdf');
    }

    /**
     * ยกเลิกใบเสร็จ (ไม่ลบ) — เลขที่ หลักฐานการชำระ และไฟล์สลิปยังอยู่ทั้งหมด
     *   invoice : ใบแจ้งหนี้กลับมาแก้ไขรายการชำระ / ออกใบเสร็จใหม่ได้
     *   advance : เงินล่วงหน้าก้อนนี้ไม่ถูกนับในยอดคงเหลือของแคมป์อีก (ต้องยังไม่ถูกหักไปใช้)
     *   direct  : ไม่นับเป็นเงินรับ
     */
    public function destroy(Request $request, $id)
    {
        $data = $request->validate(
            ['cancel_reason' => ['required', 'string', 'min:3', 'max:255']],
            ['cancel_reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก', 'cancel_reason.min' => 'เหตุผลสั้นเกินไป'],
        );

        $receipt = Receipt::with(['appliedAdvances'])->findOrFail($id);

        if ($reason = $receipt->cancelBlocker()) {
            return back()->with('error', 'ยกเลิกใบเสร็จไม่ได้: ' . $reason);
        }

        $receipt->cancel($data['cancel_reason']);

        return redirect()->route('receipts.show', $receipt->id_receipt)
            ->with('success', 'ยกเลิกใบเสร็จ ' . $receipt->code_rc . ' แล้ว'
                . ($receipt->isInvoiceType() ? ' ใบแจ้งหนี้กลับมาแก้ไขรายการชำระ / ออกใบเสร็จใหม่ได้' : ''));
    }
}
