<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function index()
    {
        $deliveryNotes = DeliveryNote::with(['customer', 'camp', 'invoice'])
            ->latest('id_delivery_note')
            ->paginate(10);

        return view('delivery_notes.index', compact('deliveryNotes'));
    }

    /** เลือกแคมป์ก่อน แล้วค่อยกรอกรายการ */
    public function create(Request $request)
    {
        $camps = Camp::with(['customer', 'quotation'])
            ->active()
            ->latest('id_camp')
            ->get();

        $camp = $request->camp
            ? Camp::with(['customer', 'quotation.details.product'])->find($request->camp)
            : null;

        $suggestedItems = $camp ? $this->buildSuggestedItems($camp) : collect();

        return view('delivery_notes.create', compact('camps', 'camp', 'suggestedItems'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_camp'              => ['required', 'exists:camps,id_camp'],
            'delivery_date'        => ['required', 'date'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.id_product'   => ['required', 'exists:products,id_product'],
            'items.*.quantity'     => ['required', 'numeric', 'gt:0'],
            'items.*.price'        => ['required', 'numeric', 'min:0'],
        ], [
            'id_camp.required'       => 'กรุณาเลือกแคมป์',
            'delivery_date.required' => 'กรุณาระบุวันที่ส่งของ',
            'items.required'         => 'ต้องมีรายการอย่างน้อย 1 รายการ',
        ]);

        $camp = Camp::findOrFail($data['id_camp']);

        $deliveryNote = DB::transaction(function () use ($camp, $data) {

            // เก็บ id_customer / id_quotation เป็นค่าจริง ไม่ไล่ตามสายโซ่ตอนพิมพ์
            $note = DeliveryNote::create([
                'id_camp'       => $camp->id_camp,
                'id_quotation'  => $camp->id_quot,
                'id_customer'   => $camp->id_customer,
                'delivery_date' => $data['delivery_date'],
                'status'        => 'delivered',
            ]);

            foreach ($data['items'] as $item) {
                DeliveryNoteDetail::create([
                    'id_delivery_note' => $note->id_delivery_note,
                    'id_product'       => $item['id_product'],
                    'quantity'         => $item['quantity'],
                    'price_per_unit'   => $item['price'],
                    'total_price'      => round($item['quantity'] * $item['price'], 2),
                ]);
            }

            return $note;
        });

        return redirect()->route('delivery-notes.show', $deliveryNote)
            ->with('ok', 'สร้างใบส่งของเรียบร้อย');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'camp', 'quotation', 'details.product', 'invoice']);

        return view('delivery_notes.show', compact('deliveryNote'));
    }

    public function pdf(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'camp', 'quotation', 'details.product']);

        return Pdf::loadView('delivery_notes.pdf', compact('deliveryNote'))
            ->setPaper('A4', 'portrait')
            ->stream('delivery-note-' . $deliveryNote->id_delivery_note . '.pdf');
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->invoice) {
            return back()->with('error', 'ใบส่งของนี้ออกใบแจ้งหนี้แล้ว ไม่สามารถลบได้');
        }

        DB::transaction(function () use ($deliveryNote) {
            $deliveryNote->details()->delete();
            $deliveryNote->delete();
        });

        return redirect()->route('delivery-notes.index')->with('ok', 'ลบใบส่งของแล้ว');
    }

    /**
     * หัวใจของ auto-fill:
     * ดึงรายการจากใบเสนอราคาของแคมป์ พร้อมคำนวณว่าส่งไปแล้วเท่าไร เหลือเท่าไร
     */
    private function buildSuggestedItems(Camp $camp)
    {
        if (! $camp->quotation) {
            return collect();
        }

        // ยอดที่ส่งไปแล้วของแคมป์นี้ แยกตามสินค้า
        $delivered = DeliveryNoteDetail::whereIn(
                'id_delivery_note',
                $camp->deliveryNotes()->pluck('id_delivery_note')
            )
            ->selectRaw('id_product, SUM(quantity) as qty')
            ->groupBy('id_product')
            ->pluck('qty', 'id_product');

        return $camp->quotation->details->map(function ($detail) use ($delivered) {
            $ordered   = (float) $detail->quantity;
            $done      = (float) ($delivered[$detail->id_product] ?? 0);
            $remaining = max($ordered - $done, 0);

            return [
                'id_product'     => $detail->id_product,
                'name_product'   => $detail->product->name_product ?? '-',
                'price_per_unit' => (float) $detail->price_per_unit,
                'ordered'        => $ordered,
                'delivered'      => $done,
                'remaining'      => $remaining,
            ];
        });
    }
}