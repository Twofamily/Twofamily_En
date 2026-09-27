<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function createFromDeliveryNote(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load('details', 'quotation');

        if ($deliveryNote->invoice) {
            return redirect()->route('invoices.show', $deliveryNote->invoice);
        }

        $invoice = Invoice::create([
            'id_customer' => $deliveryNote->id_customer,
            'id_quotation' => $deliveryNote->id_quotation,
            'id_delivery_note' => $deliveryNote->id_delivery_note,
            'discount' => $deliveryNote->quotation->discount ?? 0,
            'total' => $deliveryNote->quotation->total_amount,
            'status' => 'unpaid',
        ]);

        foreach ($deliveryNote->details as $detail) {
            InvoiceDetail::create([
                'id_invoice' => $invoice->id_invoice,
                'id_product' => $detail->id_product,
                'quantity' => $detail->quantity,
                'price' => $detail->price_per_unit,
                'total' => $detail->total_price,
            ]);
        }

        return redirect()->route('invoices.show', $invoice->id_invoice);
    }

    public function show($id)
    {
        $invoice = Invoice::with(
                'details.product',
                'customer',
                'quotation',
                'deliveryNote',
                'payments.recordedBy',
                'receipt'
            )
            ->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

        public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        $status  = $this->statusFilter($request, array_keys(Invoice::STATUSES));

        $invoices = Invoice::with(['customer', 'quotation', 'deliveryNote'])
            // ดึงยอดชำระรวม / จำนวนรายการชำระ / มีใบเสร็จไหม มาใน query เดียว
            // แทนการเรียก paidAmount() ทีละแถว
            ->withSum('payments', 'amount')     // → payments_sum_amount
            ->withCount('payments')             // → payments_count
            ->withExists('receipt')             // → receipt_exists
            // ค้นหาจากเลขที่ใบแจ้งหนี้ ชื่อลูกค้า หรือเลขที่ใบส่งของ
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('code_inv', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        ->orWhereHas('deliveryNote', fn ($d) => $d->where('code_delivery', 'like', "%{$search}%"));
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id_invoice')
            ->paginate($perPage)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'perPage', 'search', 'status'));
    }

    public function pdf($id)
    {
        $invoice = Invoice::with('details.product', 'customer', 'quotation', 'deliveryNote')
            ->findOrFail($id);

        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'));

        return $pdf->stream('INV-' . $invoice->id_invoice . '.pdf');
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

        // ลบรายละเอียดใบแจ้งหนี้ก่อน
        InvoiceDetail::where('id_invoice', $invoice->id_invoice)->delete();

        // ลบใบแจ้งหนี้
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'ลบใบแจ้งหนี้เรียบร้อยแล้ว');
    }
}