<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Customer;
use App\Models\Product;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class QuotationController extends Controller
{

    public function index()
    {
        $quotations = Quotation::with('customer')
            ->latest('id_quot')
            ->paginate(10);

        return view('quotations.index', compact('quotations'));
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
        [$subtotal, $discount, $total] = $this->calculateTotals($request->items, $request->discount);

        $quotation = Quotation::create([
            'id_customer'  => $request->id_customer,
            'date_quot'    => now(),
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'total_amount' => $total,
        ]);

        foreach ($request->items as $item) {
            QuotationDetail::create([
                'id_quot'        => $quotation->id_quot,
                'id_product'     => $item['id_product'],
                'quantity'       => $item['quantity'],
                'price_per_unit' => $item['price'],
                'total_price'    => $item['quantity'] * $item['price'],
            ]);
        }

        return redirect()->route('quotations.show', $quotation);
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('customer', 'details.product', 'camps');
        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        $quotation->load('details.product', 'customer');

        return view('quotations.edit', [
            'quotation' => $quotation,
            'customers' => Customer::all(),
            'products'  => Product::all(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->validateQuotation($request);
        [$subtotal, $discount, $total] = $this->calculateTotals($request->items, $request->discount);

        $quotation->update([
            'id_customer'  => $request->id_customer,
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'total_amount' => $total,
        ]);

        $quotation->details()->delete();

        foreach ($request->items as $item) {
            QuotationDetail::create([
                'id_quot'        => $quotation->id_quot,
                'id_product'     => $item['id_product'],
                'quantity'       => $item['quantity'],
                'price_per_unit' => $item['price'],
                'total_price'    => $item['quantity'] * $item['price'],
            ]);
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok', 'อัปเดตใบเสนอราคาเรียบร้อย');
    }

    private function validateQuotation(Request $request): void
    {
        $request->validate([
            'id_customer' => ['required', 'exists:customers,id_customer'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_product' => ['required', 'exists:products,id_product'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function calculateTotals(array $items, mixed $discount): array
    {
        $subtotal = round(collect($items)->sum(
            fn(array $item) => (float) $item['quantity'] * (float) $item['price']
        ), 2);
        $discount = min((float) $discount, $subtotal);
        $afterDiscount = round($subtotal - $discount, 2);
        $total = round($afterDiscount + round($afterDiscount * 0.07, 2), 2);

        return [$subtotal, $discount, $total];
    }

    public function downloadPDF(Quotation $quotation)
    {
        $quotation->load('customer', 'details.product');

        $pdf = Pdf::loadView('quotations.pdf', compact('quotation'))
            ->setPaper('A4', 'portrait');

        return $pdf->stream('quotation.pdf');
    }

    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);

        $quotation->details()->delete();

        $quotation->delete();

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

        $q->status = 'approved';
        $q->save();

        return back()->with('ok', 'อนุมัติใบเสนอราคาเรียบร้อย');
    }

    public function cancel($id)
    {
        $q = Quotation::findOrFail($id);

        if ($q->camps()->exists()) {
            return back()->with('error', 'มีแคมป์อ้างอิงใบเสนอราคานี้อยู่ ไม่สามารถยกเลิกได้');
        }

        if (! in_array($q->status, ['draft', 'approved'])) {
            return back()->with('error', 'ไม่สามารถยกเลิกได้');
        }

        $q->status = 'rejected';
        $q->save();

        return back()->with('ok', 'ยกเลิกใบเสนอราคาแล้ว');
    }
}
