<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function create(Request $request)
    {
        $preselect = $request->integer('delivery_note') ?: null;
        $group     = (string) $request->query('group', '') ?: null;

        if ($preselect) {
            $note = DeliveryNote::find($preselect);

            if ($note?->id_invoice) {
                return redirect()->route('invoices.show', $note->id_invoice)
                    ->with('info', 'ใบส่งของ ' . $note->code_delivery . ' ออกใบแจ้งหนี้ไปแล้ว');
            }

            $group = $note ? $this->groupKeyOf($note) : $group;
        }

        $groups = $this->pendingGroups();

        if ($group && ! $groups->has($group)) {
            $group = null;
        }

        $notes = $group ? $this->pendingNotesOf($group) : collect();

        return view('invoices.create', [
            'groups'     => $groups,
            'group'      => $group,
            'notes'      => $notes,
            'preselect'  => $preselect,
            'creditDays' => (int) Setting::get('doc.credit_days', 30),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'delivery_notes'   => ['required', 'array', 'min:1'],
            'delivery_notes.*' => ['integer', 'distinct', 'exists:delivery_notes,id_delivery_note'],
            'invoice_date'     => ['required', 'date', 'before_or_equal:today'],
            'credit_days'      => ['required', 'integer', 'between:0,180'],
        ], [
            'delivery_notes.required'      => 'กรุณาเลือกใบส่งของอย่างน้อย 1 ใบ',
            'delivery_notes.min'           => 'กรุณาเลือกใบส่งของอย่างน้อย 1 ใบ',
            'invoice_date.required'        => 'กรุณาเลือกวันที่ออกใบแจ้งหนี้',
            'invoice_date.before_or_equal' => 'วันที่ออกใบแจ้งหนี้ต้องไม่เป็นวันในอนาคต',
            'credit_days.required'         => 'กรุณาระบุเครดิตเทอม',
            'credit_days.between'          => 'เครดิตเทอมต้องอยู่ระหว่าง 0–180 วัน',
        ]);

        $invoice = DB::transaction(function () use ($data) {
            $notes = DeliveryNote::with(['details', 'camp'])
                ->whereIn('id_delivery_note', $data['delivery_notes'])
                ->lockForUpdate()
                ->get();

            $this->assertCanInvoice($notes, $data['invoice_date']);

            $first = $notes->sortBy('delivery_date')->first();

            $lines = $notes->flatMap(fn ($n) => $n->details)
                ->groupBy(fn ($d) => $d->id_product . '|' . number_format((float) $d->price_per_unit, 2, '.', ''))
                ->map(fn ($rows) => [
                    'id_product' => $rows->first()->id_product,
                    'quantity'   => round($rows->sum(fn ($d) => (float) $d->quantity), 2),
                    'price'      => (float) $rows->first()->price_per_unit,
                    'total'      => round($rows->sum(fn ($d) => (float) $d->total_price), 2),
                ])
                ->values();

            $subtotal = round($lines->sum('total'), 2);
            // แก้ไขไวยากรณ์การคำนวณ VAT
            $total    = round($subtotal + ($subtotal * Invoice::VAT_RATE / 100), 2);

            $invoice = Invoice::create([
                'invoice_date' => $data['invoice_date'],
                'credit_days'  => (int) $data['credit_days'],
                'id_customer'  => $first->id_customer,
                'id_camp'      => $first->id_camp,
                'id_quotation' => $notes->pluck('id_quotation')->filter()->first(),
                'discount'     => 0,
                'total'        => $total,
                'status'       => 'unpaid',
            ]);

            foreach ($lines as $line) {
                InvoiceDetail::create(['id_invoice' => $invoice->id_invoice] + $line);
            }

            DeliveryNote::whereIn('id_delivery_note', $notes->pluck('id_delivery_note'))
                ->update(['id_invoice' => $invoice->id_invoice]);

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('ok', 'สร้างใบแจ้งหนี้ ' . $invoice->code_inv . ' เรียบร้อย');
    }

    public function createFromDeliveryNote(DeliveryNote $deliveryNote)
    {
        return redirect()->route('invoices.create', ['delivery_note' => $deliveryNote->id_delivery_note]);
    }

    public function billReceived(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $data = $request->validate([
            'bill_received_date' => [
                'nullable',
                'date',
                'after_or_equal:' . $invoice->invoice_date->format('Y-m-d'),
                'before_or_equal:today',
            ],
        ], [
            'bill_received_date.after_or_equal'  => 'วันที่รับบิลต้องไม่ก่อนวันที่ออกใบแจ้งหนี้',
            'bill_received_date.before_or_equal' => 'วันที่รับบิลต้องไม่เป็นวันในอนาคต',
        ]);

        $invoice->update(['bill_received_date' => $data['bill_received_date'] ?? null]);

        return back()->with('ok', $invoice->bill_received_date
            ? 'บันทึกวันที่ลูกค้ารับบิลแล้ว'
            : 'ล้างวันที่รับบิลแล้ว');
    }

    public function show($id)
    {
        $invoice = Invoice::with(
                'details.product',
                'customer',
                'quotation',
                'deliveryNotes.details',
                'payments.recordedBy',
                'payments.advanceReceipt',
                'receipt',
                'camp',
                'cancelledBy'
            )
            ->withTrashed()
            ->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $statuses = $this->statusesMatching($search, Invoice::STATUSES);

        // dropdown เลือกสถานะ + ช่วงยอดสุทธิ
        $status = $this->statusFilter($request, [...array_keys(Invoice::STATUSES), 'cancelled']);
        $ranges = $this->numberRanges($request, [
            'total' => ['label' => 'ยอดสุทธิ', 'unit' => 'บาท', 'step' => '0.01'],
        ]);

        $invoices = Invoice::with(['customer', 'quotation', 'deliveryNotes:id_delivery_note,id_invoice,code_dn,delivery_date'])
            // ดึงยอดชำระรวม / จำนวนรายการชำระ / มีใบเสร็จไหม มาใน query เดียว
            // แทนการเรียก paidAmount() ทีละแถว
            ->withSum('payments', 'amount')     // → payments_sum_amount
            ->withCount('payments')             // → payments_count
            ->withExists('receipt')             // → receipt_exists
            ->with('cancelledBy')
            // แสดงใบที่ยกเลิกแล้วด้วย เลือกกรอง "ยกเลิกแล้ว" ได้
            ->cancelledFilter($status)
            // ช่องค้นหาช่องเดียว: เลขที่ใบแจ้งหนี้ ลูกค้า ใบส่งของ ใบเสนอราคา และสถานะ
            ->when($search !== '', function ($query) use ($search, $statuses) {
                $query->where(function ($sub) use ($search, $statuses) {
                    $sub->where('code_inv', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                        ->orWhereHas('deliveryNotes', fn ($d) => $d->where('code_dn', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', fn ($q) => $q->where('code_quot', 'like', "%{$search}%"));
                    if ($statuses) {
                        $sub->orWhereIn('status', $statuses);
                    }
                });
            })
            ->when($status && $status !== 'cancelled', fn ($query) => $query->where('status', $status))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'total', $ranges['total']))
            ->tap(fn ($query) => $this->applyDateRange($query, 'invoice_date', $dateFrom, $dateTo))
            ->orderByDesc('id_invoice')
            ->paginate($perPage)
            ->withQueryString();

        $pendingNotes = DeliveryNote::pendingInvoice()->count();

        return view('invoices.index', compact('invoices', 'perPage', 'search', 'dateFrom', 'dateTo', 'status', 'ranges', 'pendingNotes'));
    }

    public function pdf($id)
    {
        $invoice = Invoice::with('details.product', 'customer', 'quotation', 'deliveryNotes')
            ->withTrashed()
            ->findOrFail($id);

        $settings = Setting::pluck('value', 'key');

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'));

        return $pdf->stream($invoice->code_inv . '.pdf');
    }

    /**
     * ยกเลิกใบแจ้งหนี้ (ไม่ลบ) — เลขที่ยังอยู่ รายการสินค้ายังอยู่ให้ตรวจย้อนหลัง
     * ใบส่งของที่เคยรวมอยู่กลับไปรอออกใบแจ้งหนี้ (ออกใบใหม่ได้)
     */
    public function destroy(Request $request, $id)
    {
        $data = $request->validate(
            ['cancel_reason' => ['required', 'string', 'min:3', 'max:255']],
            ['cancel_reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก', 'cancel_reason.min' => 'เหตุผลสั้นเกินไป'],
        );

        $invoice = Invoice::findOrFail($id);

        if ($reason = $invoice->cancelBlocker()) {
            return back()->with('error', 'ยกเลิกใบแจ้งหนี้ไม่ได้: ' . $reason);
        }

        $released = DB::transaction(function () use ($invoice, $data) {
            $notes = DeliveryNote::where('id_invoice', $invoice->id_invoice)->lockForUpdate()->pluck('code_dn');
            DeliveryNote::where('id_invoice', $invoice->id_invoice)->update(['id_invoice' => null]);

            // จดใบส่งของที่ปล่อยกลับไว้ในเหตุผล จะได้ตรวจย้อนหลังได้ว่าใบนี้เคยเรียกเก็บอะไร
            $reason = $data['cancel_reason'];
            if ($notes->isNotEmpty()) {
                $reason = mb_substr($reason . ' (ใบส่งของ: ' . $notes->implode(', ') . ')', 0, 255);
            }
            $invoice->cancel($reason);

            return $notes;
        });

        return redirect()
            ->route('invoices.show', $invoice->id_invoice)
            ->with('success', 'ยกเลิกใบแจ้งหนี้ ' . $invoice->code_inv . ' แล้ว'
                . ($released->isNotEmpty() ? ' ใบส่งของ ' . $released->count() . ' ใบกลับไปรอออกใบแจ้งหนี้' : ''));
    }

    /* ================= helper ================= */

    private function assertCanInvoice($notes, string $invoiceDate): void
    {
        $fail = function (string $message, string $field = 'delivery_notes') {
            throw ValidationException::withMessages([$field => $message]);
        };

        $taken = $notes->whereNotNull('id_invoice')->pluck('code_dn');
        if ($taken->isNotEmpty()) {
            $fail('ใบส่งของ ' . $taken->implode(', ') . ' ออกใบแจ้งหนี้ไปแล้ว');
        }

        if ($notes->pluck('id_customer')->unique()->count() > 1) {
            $fail('ใบส่งของที่เลือกเป็นของลูกค้าคนละราย รวมในใบแจ้งหนี้เดียวกันไม่ได้');
        }

        if ($notes->map(fn ($n) => $n->id_camp ?? 0)->unique()->count() > 1) {
            $fail('ใบส่งของที่เลือกอยู่คนละแคมป์ รวมในใบแจ้งหนี้เดียวกันไม่ได้');
        }

        $camp = $notes->first()?->camp;
        if ($camp && ! $camp->isOpen()) {
            $fail('แคมป์ ' . $camp->code_camp . ' ปิดไปแล้ว ออกใบแจ้งหนี้ไม่ได้');
        }

        $latest = $notes->max(fn ($n) => $n->delivery_date?->format('Y-m-d'));
        if ($latest && $latest > Carbon::parse($invoiceDate)->format('Y-m-d')) {
            $fail('วันที่ออกใบแจ้งหนี้ต้องไม่ก่อนวันที่ส่งของใบล่าสุด ('
                . Carbon::parse($latest)->format('d/m/Y') . ')', 'invoice_date');
        }
    }

    private function groupKeyOf(DeliveryNote $note): string
    {
        return $note->id_camp ? 'camp-' . $note->id_camp : 'customer-' . $note->id_customer;
    }

    private function pendingGroups()
    {
        $rows = DB::table('delivery_notes as dn')
            ->join('delivery_note_details as d', 'd.id_delivery_note', '=', 'dn.id_delivery_note')
            ->whereNull('dn.id_invoice')
            ->whereNull('dn.cancelled_at')
            ->groupBy('dn.id_camp', 'dn.id_customer')
            ->selectRaw('dn.id_camp, dn.id_customer, COUNT(DISTINCT dn.id_delivery_note) as cnt, SUM(d.total_price) as amount')
            ->get();

        $camps     = Camp::withTrashed()->whereIn('id_camp', $rows->pluck('id_camp')->filter())->get()->keyBy('id_camp');
        $customers = Customer::whereIn('id_customer', $rows->pluck('id_customer'))->get()->keyBy('id_customer');

        return $rows->mapWithKeys(function ($r) use ($camps, $customers) {
            $customer = $customers->get($r->id_customer)?->name_customer ?? '-';

            if ($r->id_camp) {
                $camp  = $camps->get($r->id_camp);
                $key   = 'camp-' . $r->id_camp;
                $label = ($camp?->code_camp ?? '-') . ' · ' . ($camp?->name_camp ?? '-') . ' (' . $customer . ')';
            } else {
                $key   = 'customer-' . $r->id_customer;
                $label = $customer . ' — ขายสินค้า ไม่ผ่านแคมป์';
            }

            return [$key => [
                'label'  => $label,
                'count'  => (int) $r->cnt,
                'amount' => round((float) $r->amount, 2),
            ]];
        })->sortBy('label');
    }

    private function pendingNotesOf(string $group)
    {
        [$type, $id] = array_pad(explode('-', $group, 2), 2, null);

        return DeliveryNote::with('details.product')
            ->pendingInvoice()
            ->when($type === 'camp', fn ($q) => $q->where('id_camp', (int) $id))
            ->when($type === 'customer', fn ($q) => $q->whereNull('id_camp')->where('id_customer', (int) $id))
            ->orderBy('delivery_date')
            ->orderBy('id_delivery_note')
            ->get();
    }
}