<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function createFromDeliveryNote(Request $request, DeliveryNote $deliveryNote)
    {
        $deliveryNote->load('details', 'quotation');

        if ($deliveryNote->invoice) {
            return redirect()->route('invoices.show', $deliveryNote->invoice);
        }

        // วันที่ออกใบแจ้งหนี้: ห้ามก่อนวันส่งของ และห้ามเป็นวันในอนาคต
        $data = $request->validate([
            'invoice_date' => [
                'required',
                'date',
                'after_or_equal:' . $deliveryNote->delivery_date->format('Y-m-d'),
                'before_or_equal:today',
            ],
        ], [
            'invoice_date.required'        => 'กรุณาเลือกวันที่ออกใบแจ้งหนี้',
            'invoice_date.after_or_equal'  => 'วันที่ออกใบแจ้งหนี้ต้องไม่ก่อนวันที่ส่งของ ('
                                              . $deliveryNote->delivery_date->format('d/m/Y') . ')',
            'invoice_date.before_or_equal' => 'วันที่ออกใบแจ้งหนี้ต้องไม่เป็นวันในอนาคต',
        ]);

        // ใช้ transaction เพื่อให้หัวใบแจ้งหนี้ รายการ และตัวนับเลขที่เอกสาร
        // สำเร็จพร้อมกันทั้งหมด หรือ rollback ทั้งหมด
        // ถ้าล้มกลางทาง จะไม่เหลือใบแจ้งหนี้ที่ไม่มีรายการ และเลขที่จะไม่ข้าม
        $invoice = DB::transaction(function () use ($deliveryNote, $data) {

            // ใบแจ้งหนี้ไม่มีส่วนลด: ยอดสุทธิ = รายการที่ส่งจริงในใบส่งของนี้ + VAT
            $subtotal = round($deliveryNote->details->sum(fn ($d) => (float) $d->total_price), 2);
            $total    = round($subtotal + $subtotal * Invoice::VAT_RATE / 100, 2);

            $invoice = Invoice::create([
                'invoice_date'     => $data['invoice_date'],
                'id_customer'      => $deliveryNote->id_customer,
                'id_camp'          => $deliveryNote->id_camp,
                'id_quotation'     => $deliveryNote->id_quotation,
                'id_delivery_note' => $deliveryNote->id_delivery_note,
                'discount'         => 0,
                'total'            => $total,
                'status'           => 'unpaid',
            ]);

            foreach ($deliveryNote->details as $detail) {
                InvoiceDetail::create([
                    'id_invoice' => $invoice->id_invoice,
                    'id_product' => $detail->id_product,
                    'quantity'   => $detail->quantity,
                    'price'      => $detail->price_per_unit,
                    'total'      => $detail->total_price,
                ]);
            }

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('ok', 'สร้างใบแจ้งหนี้ ' . $invoice->code_inv . ' เรียบร้อย');
    }

    public function show($id)
    {
        $invoice = Invoice::with(
                'details.product',
                'customer',
                'quotation',
                'deliveryNote',
                'payments.recordedBy',
                'payments.advanceReceipt',
                'receipt',
                'camp'
            )
            ->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        [$dateFrom, $dateTo] = $this->dateRange($request);

        // พิมพ์ "ยังไม่ชำระ" / "บางส่วน" / "ครบแล้ว" ก็ค้นตามสถานะการชำระได้
        $statuses = $this->statusesMatching($search, Invoice::STATUSES);

        // dropdown เลือกสถานะ + ช่วงยอดสุทธิ
        $status = $this->statusFilter($request, array_keys(Invoice::STATUSES));
        $ranges = $this->numberRanges($request, [
            'total' => ['label' => 'ยอดสุทธิ', 'unit' => 'บาท', 'step' => '0.01'],
        ]);

        $invoices = Invoice::with(['customer', 'quotation', 'deliveryNote'])
            // ดึงยอดชำระรวม / จำนวนรายการชำระ / มีใบเสร็จไหม มาใน query เดียว
            // แทนการเรียก paidAmount() ทีละแถว
            ->withSum('payments', 'amount')     // → payments_sum_amount
            ->withCount('payments')             // → payments_count
            ->withExists('receipt')             // → receipt_exists
            // ช่องค้นหาช่องเดียว: เลขที่ใบแจ้งหนี้ ลูกค้า ใบส่งของ ใบเสนอราคา และสถานะ
            ->when($search !== '', function ($query) use ($search, $statuses) {
                $query->where(function ($sub) use ($search, $statuses) {
                    $sub->where('code_inv', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        // code_delivery เป็นแค่ accessor ต้องค้นจากคอลัมน์จริง code_dn
                        ->orWhereHas('deliveryNote', fn ($d) => $d->where('code_dn', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($q) => $q->where('code_quot', 'like', "%{$search}%"));
                    if ($statuses) {
                        $sub->orWhereIn('status', $statuses);
                    }
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'total', $ranges['total']))
            ->tap(fn ($query) => $this->applyDateRange($query, 'invoice_date', $dateFrom, $dateTo))
            ->orderByDesc('id_invoice')
            ->paginate($perPage)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'perPage', 'search', 'dateFrom', 'dateTo', 'status', 'ranges'));
    }

    public function pdf($id)
    {
        $invoice = Invoice::with('details.product', 'customer', 'quotation', 'deliveryNote')
            ->findOrFail($id);

        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'));

        return $pdf->stream($invoice->code_inv . '.pdf');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->hasReceipt()) {
            return back()->with('error', 'ใบแจ้งหนี้นี้ออกใบเสร็จแล้ว ไม่สามารถลบได้');
        }

        if ($invoice->hasPayments()) {
            return back()->with('error', 'ใบแจ้งหนี้นี้มีรายการชำระเงินอยู่ กรุณาลบรายการชำระก่อน');
        }

        DB::transaction(function () use ($invoice) {
            InvoiceDetail::where('id_invoice', $invoice->id_invoice)->delete();
            $invoice->delete();
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'ลบใบแจ้งหนี้เรียบร้อยแล้ว');
    }
}