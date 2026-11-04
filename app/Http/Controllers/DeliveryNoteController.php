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
    public const INVOICE_STATUSES = [
        'pending'  => 'รอออกใบแจ้งหนี้',
        'invoiced' => 'ออกใบแจ้งหนี้แล้ว',
        'cancelled' => 'ยกเลิกแล้ว',
    ];

        public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $statuses = $this->statusesMatching($search, self::INVOICE_STATUSES);

        $status = $this->statusFilter($request, array_keys(self::INVOICE_STATUSES));

        $deliveryNotes = DeliveryNote::with(['customer', 'camp', 'invoice', 'quotation'])
            ->cancelledFilter($status)
            ->when($search !== '', function ($query) use ($search, $statuses) {
                $query->where(function ($sub) use ($search, $statuses) {
                    $sub->where('code_dn', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        ->orWhereHas('camp', fn ($c) => $c->where('code_camp', 'like', "%{$search}%")
                                                         ->orWhere('name_camp', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($q) => $q->where('code_quot', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($i) => $i->where('code_inv', 'like', "%{$search}%"));
                    if (in_array('invoiced', $statuses, true)) {
                        $sub->orWhereHas('invoice');
                    }
                    if (in_array('pending', $statuses, true)) {
                        $sub->orWhereDoesntHave('invoice');
                    }
                });
            })
            ->when($status === 'invoiced', fn ($query) => $query->whereHas('invoice'))
            ->when($status === 'pending', fn ($query) => $query->whereDoesntHave('invoice'))
            ->tap(fn ($query) => $this->applyDateRange($query, 'delivery_date', $dateFrom, $dateTo))
            ->latest('id_delivery_note')
            ->paginate($perPage)
            ->withQueryString();

        return view('delivery_notes.index', [
            'deliveryNotes' => $deliveryNotes,
            'perPage'       => $perPage,
            'search'        => $search,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'status'        => $status,
        ] + $this->createFormData($request));
    }

    private function createFormData(Request $request): array
    {
        if (! $request->user()?->canEdit('delivery_notes')) {
            return ['canCreate' => false];
        }

        $camps = Camp::with(['customer', 'quotation'])
            ->active()
            ->latest('id_camp')
            ->get();

        $campId = $request->input('camp') ?: old('id_camp');
        $camp = $campId
            ? Camp::with(['customer', 'quotation.details.product', 'salesOrders'])->find($campId)
            : null;

        $suggestedItems = $camp ? $this->buildSuggestedItems($camp) : collect();

        return ['canCreate' => true] + compact('camps', 'camp', 'suggestedItems');
    }

    public function create(Request $request)
    {
        return redirect()->route('delivery_notes.index', array_filter([
            'modal' => 'create',
            'camp'  => $request->input('camp'),
        ]));
    }

    public function store(Request $request)
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => is_array($item)
                && ($item['quantity'] ?? '') !== ''
                && ! (is_numeric($item['quantity']) && (float) $item['quantity'] == 0))
            ->all();

        $data = validator(array_merge($request->all(), ['items' => $items]), [
            'id_camp'              => ['required', 'exists:camps,id_camp'],
            'delivery_date'        => ['required', 'date'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.id_product'   => ['required', 'exists:products,id_product'],
            'items.*.quantity'     => ['required', 'numeric', 'gt:0'],
            'items.*.price'        => ['required', 'numeric', 'min:0'],
        ], [
            'id_camp.required'         => 'กรุณาเลือกแคมป์',
            'id_camp.exists'           => 'ไม่พบแคมป์ที่เลือก',
            'delivery_date.required'   => 'กรุณาระบุวันที่ส่งของ',
            'delivery_date.date'       => 'วันที่ส่งของไม่ถูกต้อง',
            'items.required'           => 'กรุณาใส่จำนวนที่ส่งมากกว่า 0 อย่างน้อย 1 รายการ',
            'items.min'                => 'กรุณาใส่จำนวนที่ส่งมากกว่า 0 อย่างน้อย 1 รายการ',
            'items.*.id_product.exists' => 'ไม่พบสินค้าในระบบ',
            'items.*.quantity.numeric' => 'จำนวนที่ส่งต้องเป็นตัวเลข',
            'items.*.quantity.gt'      => 'จำนวนที่ส่งต้องมากกว่า 0 (ติดลบไม่ได้)',
            'items.*.price.required'   => 'กรุณาใส่ราคาต่อหน่วย',
            'items.*.price.numeric'    => 'ราคาต่อหน่วยต้องเป็นตัวเลข',
            'items.*.price.min'        => 'ราคาต่อหน่วยต้องไม่ติดลบ',
        ])->validateWithBag('createDeliveryNote');

        $camp = Camp::findOrFail($data['id_camp']);

        if (! $camp->isWorking()) {
            return back()->withInput()->withErrors(['id_camp' =>
                'แคมป์ ' . $camp->code_camp . ' อยู่ในสถานะ "' . $camp->status_label . '" ออกใบส่งของเพิ่มไม่ได้'], 'createDeliveryNote');
        }

        if (! $request->boolean('confirm_over')) {
            $over = $this->overContract($camp, $data['items']);

            if ($over) {
                return back()->withInput()
                    ->withErrors($over, 'createDeliveryNote')
                    ->with('over_contract', true);
            }
        }

        $deliveryNote = DB::transaction(function () use ($camp, $data) {

            $note = DeliveryNote::create([
                'id_camp'       => $camp->id_camp,
                'id_quotation'  => $camp->id_quot ?? $camp->contractSalesOrders()->orderBy('id_so')->value('id_quot'),
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

            $camp->markStartedIfPreparing();

            return $note;
        });

        return redirect()->route('delivery_notes.show', $deliveryNote)
            ->with('ok', 'สร้างใบส่งของเรียบร้อย');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'camp', 'quotation', 'details.product', 'invoice', 'cancelledBy']);

        return view('delivery_notes.show', compact('deliveryNote'));
    }

    public function pdf(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'camp', 'quotation', 'details.product']);

        return Pdf::loadView('delivery_notes.pdf', compact('deliveryNote'))
            ->setPaper('A4', 'portrait')
            ->stream('delivery-note-' . $deliveryNote->id_delivery_note . '.pdf');
    }

    public function destroy(Request $request, DeliveryNote $deliveryNote)
    {
        $data = $request->validate(
            ['cancel_reason' => ['required', 'string', 'min:3', 'max:255']],
            ['cancel_reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก', 'cancel_reason.min' => 'เหตุผลสั้นเกินไป'],
        );

        if ($reason = $deliveryNote->cancelBlocker()) {
            return back()->with('error', 'ยกเลิกใบส่งของไม่ได้: ' . $reason);
        }

        $deliveryNote->cancel($data['cancel_reason']);

        return redirect()->route('delivery_notes.show', $deliveryNote)
            ->with('ok', 'ยกเลิกใบส่งของ ' . $deliveryNote->code_delivery . ' แล้ว');
    }

    private function overContract(Camp $camp, array $items): array
    {
        $remaining = $this->buildSuggestedItems($camp)->keyBy('id_product');
        $used = [];
        $errors = [];

        foreach ($items as $i => $item) {
            $id = (int) $item['id_product'];
            $used[$id] = ($used[$id] ?? 0) + (float) $item['quantity'];
            $left = (float) ($remaining[$id]['remaining'] ?? 0);

            if ($used[$id] > $left + 0.005) {
                $errors["items.$i.quantity"] = 'เกินจำนวนคงเหลือตามสัญญา (เหลือ ' . number_format($left, 2) . ')';
            }
        }

        return $errors;
    }

    private function buildSuggestedItems(Camp $camp)
    {
        $contract = $camp->contractItems();

        if ($contract->isEmpty()) {
            return collect();
        }

        $delivered = DeliveryNoteDetail::whereIn(
            'id_delivery_note',
            $camp->deliveryNotes()->pluck('id_delivery_note')
        )
            ->selectRaw('id_product, SUM(quantity) as qty')
            ->groupBy('id_product')
            ->pluck('qty', 'id_product');

        return $contract->map(function (array $item) use ($delivered) {
            $ordered   = (float) $item['quantity'];
            $done      = (float) ($delivered[$item['id_product']] ?? 0);
            $remaining = max($ordered - $done, 0);

            return [
                'id_product'     => $item['id_product'],
                'name_product'   => $item['name_product'],
                'price_per_unit' => (float) $item['price_per_unit'],
                'ordered'        => $ordered,
                'delivered'      => $done,
                'remaining'      => $remaining,
            ];
        });
    }
}
