<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function createFromInvoice($id)
    {
        $invoice = Invoice::with('receipt')->findOrFail($id);

        // ออกไปแล้ว → พาไปดูใบเดิม ไม่สร้างซ้ำ
        if ($invoice->hasReceipt()) {
            return redirect()
                ->route('receipts.show', $invoice->receipt->id_receipt)
                ->with('warning', 'ใบแจ้งหนี้นี้ออกใบเสร็จไปแล้ว');
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
        $receipt = Receipt::create([
            'id_invoice'   => $invoice->id_invoice,
            'id_customer'  => $invoice->id_customer,
            'total'        => $invoice->total,
            'date_receipt' => now(),
        ]);

        return redirect()
            ->route('receipts.show', $receipt->id_receipt)
            ->with('success', 'ออกใบเสร็จ ' . $receipt->code_rc . ' เรียบร้อย');
    }

    public function show($id)
    {
        $receipt = Receipt::with(
                'invoice.customer',
                'invoice.details.product',
                'invoice.quotation',
                'invoice.deliveryNote',
                'invoice.payments.recordedBy'
            )
            ->findOrFail($id);

        return view('receipts.show', compact('receipt'));
    }

    public function index(Request $request)
    {
        $perPage  = $this->perPage($request);
        $search   = $this->searchTerm($request);
        $dateFrom = $this->dateInput($request, 'date_from');
        $dateTo   = $this->dateInput($request, 'date_to');

        // ผู้ใช้เลือกวันที่สลับกัน → สลับให้เอง แทนที่จะได้ผลว่าง
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = Receipt::with('invoice.customer')
            // ค้นหาจากเลขที่ใบเสร็จ เลขที่ใบแจ้งหนี้ หรือชื่อลูกค้า
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('code_rc', 'like', "%{$search}%")
                        ->orWhereHas('invoice', function ($inv) use ($search) {
                            $inv->where('code_inv', 'like', "%{$search}%")
                                ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($dateFrom, fn ($query) => $query->whereDate('date_receipt', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('date_receipt', '<=', $dateTo));

        // ยอดรวมรับชำระตามเงื่อนไข (ทุกหน้า ไม่ใช่เฉพาะหน้าที่แสดง)
        $sumTotal = (clone $query)->sum('total');

        $receipts = $query
            ->orderByDesc('id_receipt')
            ->paginate($perPage)
            ->withQueryString();

        return view('receipts.index', compact(
            'receipts', 'perPage', 'search', 'dateFrom', 'dateTo', 'sumTotal'
        ));
    }

    public function pdf($id)
    {
        $receipt = Receipt::with('invoice.customer', 'invoice.details.product', 'invoice.quotation')
            ->findOrFail($id);

        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('receipts.pdf', compact('receipt', 'settings'));

        return $pdf->stream('RC-' . $receipt->id_receipt . '.pdf');
    }

    public function destroy($id)
    {
        $receipt = Receipt::findOrFail($id);
        $receipt->delete();

        return redirect()->route('receipts.index')
            ->with('success', 'ลบใบเสร็จเรียบร้อย');
    }

    /* ==================== Private helpers ==================== */

    /**
     * อ่านวันที่รูปแบบ Y-m-d จาก query string
     * รูปแบบผิดหรือเป็นวันที่ไม่มีจริง (เช่น 2026-02-30) → null
     */
    private function dateInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }
}