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
        $invoice = Invoice::with('details.product', 'customer', 'quotation', 'deliveryNote')
            ->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

    public function pay($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->status = 'paid';
        $invoice->save();

        return back()->with('success', 'ชำระเงินแล้ว');
    }

    public function index()
    {
        $invoices = Invoice::with('customer')
            ->orderByDesc('id_invoice')
            ->paginate(10);

        return view('invoices.index', compact('invoices'));
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

        // ลบรายละเอียดใบแจ้งหนี้ก่อน
        InvoiceDetail::where('id_invoice', $invoice->id_invoice)->delete();

        // ลบใบแจ้งหนี้
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'ลบใบแจ้งหนี้เรียบร้อยแล้ว');
    }
}
