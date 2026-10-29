<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\CampTruck;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DeliveryNoteDetail;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        // พิมพ์ "เปิด" / "ปิด" ก็ค้นตามสถานะได้
        $statuses = $this->statusesMatching($search, Camp::STATUS_LABELS);

        // dropdown เลือกสถานะ + จังหวัดที่ตั้งแคมป์
        $status   = $this->statusFilter($request, array_keys(Camp::STATUS_LABELS));
        $province = trim((string) $request->input('province', ''));

        // จังหวัดที่มีแคมป์อยู่จริง ใช้เป็นตัวเลือก (ไม่ต้องโชว์ 77 จังหวัด)
        $provinces = Camp::whereNotNull('province')->where('province', '!=', '')
            ->distinct()->orderBy('province')->pluck('province');

        // ช่องค้นหาช่องเดียว: ชื่อ/รหัสแคมป์ ที่อยู่ ผู้ติดต่อ ลูกค้า เลขใบเสนอราคา และสถานะ
        $camps = Camp::with(['customer', 'quotation'])
            ->withCount(['trucks' => fn ($query) => $query->whereNull('released_date')])
            ->when($search !== '', fn ($query) => $query->where(function ($x) use ($search, $statuses) {
                foreach (['name_camp', 'code_camp', 'address_detail', 'subdistrict', 'district',
                          'province', 'contact_name', 'contact_phone'] as $col) {
                    $x->orWhere($col, 'like', "%{$search}%");
                }
                $x->orWhereHas('customer', fn ($c) => $c->where('name_customer', 'like', "%{$search}%"))
                  ->orWhereHas('quotation', fn ($q) => $q->where('code_quot', 'like', "%{$search}%"))
                  ->orWhereHas('salesOrders', fn ($q) => $q->where('code_so', 'like', "%{$search}%"));
                if ($statuses) {
                    $x->orWhereIn('status_camp', $statuses);
                }
            }))
            ->when($status, fn ($query) => $query->where('status_camp', $status))
            ->when($province !== '', fn ($query) => $query->where('province', $province))
            ->latest('id_camp')
            ->paginate($perPage)
            ->withQueryString();

        return view('camps.index', compact('camps', 'search', 'perPage', 'status', 'province', 'provinces'));
    }

    /**
     * เพิ่มแคมป์
     * ?sales_order=ID → เปิดแคมป์ใหม่จากใบสั่งขาย (เติมลูกค้า/ใบเสนอราคาให้ และผูกใบสั่งขายเข้าแคมป์ตอนบันทึก)
     */
    public function create(Request $request)
    {
        $salesOrder = null;

        if ($request->filled('sales_order')) {
            $salesOrder = SalesOrder::with('customer')->find($request->sales_order);

            if (! $salesOrder || ! $salesOrder->canOpenCamp()) {
                return redirect()
                    ->route('sales_orders.index')
                    ->with('error', 'ใบสั่งขายนี้เปิดแคมป์ไม่ได้ (ต้องยืนยันแล้ว และยังไม่อยู่ในแคมป์ไหน)');
            }
        }

        $camp = new Camp([
            'id_customer' => $salesOrder?->id_customer,
            'id_quot'     => $salesOrder?->id_quot,
            'status_camp' => 'preparing',
        ]);

        return view('camps.create', [
            'camp'       => $camp,
            'salesOrder' => $salesOrder,
            'customers'  => Customer::orderBy('name_customer')->get(),
            'quotations' => $this->quotationOptions(),
            'isEdit'     => false,
            // ใบสั่งขายที่ยืนยันแล้วและยังไม่อยู่ในแคมป์ไหน — ให้เลือกเปิดแคมป์จากใบสั่งขายได้เลย
            'openableOrders' => $salesOrder ? collect() : SalesOrder::with('customer')
                ->where('status', 'confirmed')
                ->whereNull('id_camp')
                ->orderByDesc('id_so')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCamp($request);

        // เปิดจากใบสั่งขาย → ตรวจว่ายังเปิดได้ และลูกค้าตรงกัน
        $salesOrder = null;

        if ($request->filled('id_so')) {
            $salesOrder = SalesOrder::find($request->input('id_so'));

            if (! $salesOrder || ! $salesOrder->canOpenCamp()) {
                return back()->withInput()->with('error', 'ใบสั่งขายนี้เปิดแคมป์ไม่ได้แล้ว');
            }

            if ((int) $salesOrder->id_customer !== (int) $data['id_customer']) {
                return back()->withInput()->with('error', 'ลูกค้าของแคมป์ต้องตรงกับลูกค้าในใบสั่งขาย ' . $salesOrder->code_so);
            }
        }

        if ($error = $this->checkQuotationMatch($data)) {
            return back()->withInput()->with('error', $error);
        }

        $data['code_camp'] = Camp::generateCode();
        $data['status_camp'] = 'preparing';   // แคมป์ใหม่เริ่มที่เตรียมงานเสมอ

        // สร้างแคมป์ + ผูกใบสั่งขาย สำเร็จพร้อมกัน หรือไม่สำเร็จทั้งคู่
        $camp = DB::transaction(function () use ($data, $salesOrder) {
            $camp = Camp::create($data);

            $salesOrder?->update([
                'id_camp' => $camp->id_camp,
                'status'  => 'in_progress',
            ]);

            return $camp;
        });

        return redirect()->route('camps.show', $camp)->with('ok', $salesOrder
            ? "เปิดแคมป์ {$camp->code_camp} จากใบสั่งขาย {$salesOrder->code_so} เรียบร้อย"
            : 'เพิ่มแคมป์เรียบร้อย');
    }

    public function edit(Camp $camp)
    {
        // จัดการรถประจำแคมป์ (เพิ่ม / ถอนออก) ทำในหน้าแก้ไขเท่านั้น หน้าดูข้อมูลเป็น read-only
        $camp->load([
            'trucks' => fn ($q) => $q->with(['brand', 'model'])->orderByPivot('assigned_date', 'desc'),
        ]);

        return view('camps.edit', [
            'camp'            => $camp,
            'customers'       => Customer::orderBy('name_customer')->get(),
            'quotations'      => $this->quotationOptions($camp),
            'availableTrucks' => $this->availableTrucksFor($camp),
            'availableDrivers' => Driver::available()->orderBy('fname_driver')->get(),
            'isEdit'          => true,
        ]);
    }

    public function update(Request $request, Camp $camp)
    {
        $data = $this->validateCamp($request, $camp);

        if ($error = $this->checkQuotationMatch($data)) {
            return back()->withInput()->with('error', $error);
        }

        // เปลี่ยนเป็นปิดแคมป์ผ่านหน้าแก้ไข → ต้องผ่านเงื่อนไขเดียวกับปุ่มปิดแคมป์
        if ($data['status_camp'] === 'closed' && $camp->status_camp !== 'closed'
            && ($blockers = $camp->closeBlockers())) {
            return back()->withInput()->withErrors(['status_camp' => 'ยังปิดแคมป์ไม่ได้: ' . implode(' · ', $blockers)]);
        }

        $camp->update($data);

        return redirect()->route('camps.show', $camp)->with('ok', 'แก้ไขแคมป์เรียบร้อย');
    }

    /**
     * ลบได้เฉพาะแคมป์ที่ยังไม่มีเอกสาร / บันทึกน้ำมัน / รถประจำ (เช่น เปิดผิด)
     * ใบสั่งขายที่ผูกไว้จะกลับเป็น "ยืนยันแล้ว" ให้เปิดแคมป์ใหม่ได้
     */
    public function destroy(Camp $camp)
    {
        if ($reason = $camp->deleteBlocker()) {
            return back()->with('error', "ลบแคมป์ {$camp->code_camp} ไม่ได้: {$reason}");
        }

        DB::transaction(function () use ($camp) {
            SalesOrder::where('id_camp', $camp->id_camp)
                ->where('status', 'in_progress')
                ->update(['id_camp' => null, 'status' => 'confirmed']);
            SalesOrder::where('id_camp', $camp->id_camp)->update(['id_camp' => null]);

            $camp->delete();
        });

        return redirect()->route('camps.index')->with('ok', 'ลบแคมป์แล้ว');
    }

    /**
     * เปลี่ยนสถานะแคมป์ทีละขั้นจากหน้าแคมป์
     * preparing → active → completed → closed  (ปิดแล้วเปิดกลับเป็น active ได้)
     */
    public function changeStatus(Request $request, Camp $camp)
    {
        $data = $request->validate([
            'status_camp' => ['required', Rule::in(array_keys(Camp::STATUS_LABELS))],
        ]);

        $to = $data['status_camp'];

        if ($to === $camp->status_camp) {
            return back()->with('info', 'สถานะไม่มีการเปลี่ยนแปลง');
        }

        if ($to === 'closed' && ($blockers = $camp->closeBlockers())) {
            return back()->withErrors(['status_camp' => 'ยังปิดแคมป์ไม่ได้: ' . implode(' · ', $blockers)]);
        }

        $camp->update(['status_camp' => $to]);

        return back()->with('ok', 'เปลี่ยนสถานะแคมป์ ' . $camp->code_camp . ' เป็น "' . $camp->status_label . '" แล้ว');
    }

    public function show(Camp $camp)
    {
        $camp->load([
            'customer',
            'quotation.details.product',
            'salesOrders' => fn ($q) => $q->orderBy('id_so'),
            'trucks' => fn($q) => $q->orderByPivot('assigned_date', 'desc'),
        ]);

        $deliveryNotes = $camp->deliveryNotes()
            ->with(['invoice', 'details'])
            ->latest('delivery_date')
            ->get();

        // ใบเสร็จทุกแบบที่ผูกแคมป์นี้ (ชำระตามใบแจ้งหนี้ / ขายสด)
        $receipts = $camp->receipts()
            ->latest('date_receipt')
            ->get();

        $progress = $this->buildProgress($camp);
        $summary  = $camp->financialSummary();

        // ใบส่งของที่ยังไม่ได้เรียกเก็บ → ปุ่ม "ออกใบแจ้งหนี้" บนหน้าแคมป์
        $pendingNotes = $deliveryNotes->whereNull('id_invoice');

        // เงื่อนไขปิดแคมป์ (คำนวณเฉพาะตอนที่ขั้นถัดไปคือปิดแคมป์)
        $closeBlockers = $camp->status_camp === 'completed' ? $camp->closeBlockers() : [];

        return view('camps.show', compact(
            'camp', 'deliveryNotes', 'progress', 'receipts', 'summary', 'pendingNotes', 'closeBlockers'
        ));
    }

    /** รถที่พร้อมใช้งาน และยังไม่ได้ประจำแคมป์ไหนอยู่ (รถ 1 คัน ทำงานได้ทีละแคมป์) */
    private function availableTrucksFor(Camp $camp)
    {
        return Truck::available()
            ->notInActiveCamp()
            ->with(['brand', 'model'])
            ->orderBy('id_truck')
            ->get();
    }

    /** เทียบยอดตามสัญญา (ใบสั่งขายทุกใบในแคมป์ หรือใบเสนอราคาเดิม) กับยอดที่ส่งจริงสะสม */
    private function buildProgress(Camp $camp)
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
            $ordered = (float) $item['quantity'];
            $done    = (float) ($delivered[$item['id_product']] ?? 0);

            return [
                'name_product' => $item['name_product'],
                'ordered'      => $ordered,
                'delivered'    => $done,
                'remaining'    => max($ordered - $done, 0),
                'over'         => max($done - $ordered, 0),
                'percent'      => $ordered > 0 ? min(round($done / $ordered * 100), 100) : 0,
            ];
        });
    }

    public function assignTruck(Request $request, Camp $camp)
    {
        $data = $request->validate([
            'id_truck'      => ['required', Rule::exists('trucks', 'id_truck')->where('status_truck', 'active')],
            'id_driver'     => ['nullable', Rule::exists('drivers', 'id_driver')->whereNull('deleted_at')],
            'assigned_date' => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
        ], [
            'id_truck.exists'  => 'รถคันนี้ไม่พร้อมใช้งาน',
            'id_driver.exists' => 'ไม่พบพนักงานขับรถที่เลือก',
        ]);

        // รถ 1 คัน ทำงานได้ทีละแคมป์
        $truckBusy = CampTruck::with('camp')
            ->where('id_truck', $data['id_truck'])
            ->whereNull('released_date')
            ->first();

        if ($truckBusy) {
            return back()->with('warning', (int) $truckBusy->id_camp === (int) $camp->id_camp
                ? 'รถคันนี้ประจำแคมป์นี้อยู่แล้ว'
                : 'รถคันนี้ยังทำงานอยู่ที่แคมป์ ' . ($truckBusy->camp->code_camp ?? '-') . ' ต้องถอนออกจากแคมป์นั้นก่อน');
        }

        // คนขับ 1 คน ทำงานได้ทีละแคมป์
        if ($error = $this->driverBusyMessage($data['id_driver'] ?? null)) {
            return back()->with('warning', $error);
        }

        $camp->trucks()->attach($data['id_truck'], [
            'id_driver'     => $data['id_driver'] ?? null,
            'assigned_date' => $data['assigned_date'],
            'note'          => $data['note'] ?? null,
        ]);

        return back()->with('ok', "เพิ่มรถ {$data['id_truck']} เข้าแคมป์แล้ว");
    }

    /**
     * เปลี่ยน/ใส่คนขับของรถที่ยังทำงานอยู่ในแคมป์ (เช่น สลับกะ หรือรถที่มอบหมายไว้ก่อนมีระบบคนขับ)
     * ไม่ได้แก้ประวัติย้อนหลัง — ถ้าต้องการเก็บประวัติว่าใครขับช่วงไหน ให้ถอนรถแล้วเพิ่มใหม่พร้อมคนขับคนใหม่
     */
    public function changeDriver(Request $request, Camp $camp, $assignmentId)
    {
        $assignment = CampTruck::where('id_camp', $camp->id_camp)
            ->where('id_assignment', $assignmentId)
            ->firstOrFail();

        if (! $assignment->isActive()) {
            return back()->with('error', 'รถคันนี้ถอนออกจากแคมป์แล้ว เปลี่ยนคนขับไม่ได้');
        }

        $data = $request->validate([
            'id_driver' => ['nullable', Rule::exists('drivers', 'id_driver')->whereNull('deleted_at')],
        ], [
            'id_driver.exists' => 'ไม่พบพนักงานขับรถที่เลือก',
        ]);

        $newDriver = $data['id_driver'] ?? null;

        if ($newDriver && (int) $newDriver !== (int) $assignment->id_driver
            && ($error = $this->driverBusyMessage($newDriver))) {
            return back()->with('warning', $error);
        }

        $assignment->update(['id_driver' => $newDriver]);

        return back()->with('ok', $newDriver
            ? "เปลี่ยนคนขับของรถ {$assignment->id_truck} แล้ว"
            : "เอาคนขับออกจากรถ {$assignment->id_truck} แล้ว");
    }

    /** คืนข้อความเตือนถ้าคนขับคนนี้ประจำแคมป์อื่นอยู่ (null = ว่าง) */
    private function driverBusyMessage($idDriver): ?string
    {
        if (! $idDriver) {
            return null;
        }

        $busy = CampTruck::with(['camp', 'driver'])
            ->where('id_driver', $idDriver)
            ->whereNull('released_date')
            ->first();

        if (! $busy) {
            return null;
        }

        return 'คนขับ ' . ($busy->driver->full_name ?? '') . ' ยังขับรถ ' . $busy->id_truck
            . ' อยู่ที่แคมป์ ' . ($busy->camp->code_camp ?? '-') . ' ต้องถอนออกหรือเปลี่ยนคนขับก่อน';
    }

    public function releaseTruck(Request $request, Camp $camp, $assignmentId)
    {
        $assignment = CampTruck::where('id_camp', $camp->id_camp)
            ->where('id_assignment', $assignmentId)
            ->firstOrFail();

        $data = $request->validate([
            'released_date' => ['required', 'date', 'after_or_equal:' . $assignment->assigned_date->format('Y-m-d')],
        ], [
            'released_date.after_or_equal' => 'วันที่ถอนรถต้องไม่ก่อนวันที่เริ่มทำงาน',
        ]);

        // ถอนรถ = คนขับของรถคันนั้นออกจากแคมป์ไปด้วย (อยู่แถวเดียวกัน)
        $assignment->update(['released_date' => $data['released_date']]);

        return back()->with('ok', 'ถอนรถ' . ($assignment->id_driver ? 'และคนขับ' : '') . 'ออกจากแคมป์แล้ว');
    }

    /**
     * ใบเสนอราคาที่เลือกได้ = อนุมัติแล้ว
     * ถ้าเป็นหน้าแก้ไข ให้พ่วงใบเดิมมาด้วยเสมอ กันใบหายจาก dropdown
     */
    private function quotationOptions(?Camp $camp = null)
    {
        return Quotation::with('customer')
            ->where(function ($query) use ($camp) {
                $query->where('status', 'approved');

                if ($camp?->id_quot) {
                    $query->orWhere('id_quot', $camp->id_quot);
                }
            })
            ->latest('id_quot')
            ->get();
    }

    /** ใบเสนอราคาต้องเป็นของลูกค้ารายเดียวกับแคมป์ */
    private function checkQuotationMatch(array $data): ?string
    {
        if (empty($data['id_quot'])) {
            return null;
        }

        $quotation = Quotation::find($data['id_quot']);

        if (! $quotation) {
            return 'ไม่พบใบเสนอราคาที่เลือก';
        }

        if ((int) $quotation->id_customer !== (int) $data['id_customer']) {
            return 'ใบเสนอราคาที่เลือกเป็นของลูกค้ารายอื่น กรุณาตรวจสอบอีกครั้ง';
        }

        return null;
    }

    private function validateCamp(Request $request, ?Camp $camp = null): array
    {
        return $request->validate([
            'id_customer'    => ['required', 'exists:customers,id_customer'],
            'id_quot'        => [
                'nullable',
                Rule::exists('quotations', 'id_quot')->where(function ($query) use ($camp) {
                    $query->whereNull('deleted_at');

                    if ($camp?->id_quot) {
                        $query->where(fn($q) => $q->where('status', 'approved')
                                                  ->orWhere('id_quot', $camp->id_quot));
                    } else {
                        $query->where('status', 'approved');
                    }
                }),
            ],
            'name_camp'      => ['required', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'subdistrict'    => ['required', 'string', 'max:100'],
            'district'       => ['required', 'string', 'max:100'],
            'province'       => ['required', 'string', 'max:100'],
            'zipcode'        => ['nullable', 'digits:5'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
            'contact_name'   => ['nullable', 'string', 'max:255'],
            'contact_phone'  => ['nullable', 'digits:10'],
            'status_camp'    => ['required', Rule::in(array_keys(Camp::STATUS_LABELS))],
            'note'           => ['nullable', 'string', 'max:1000'],
        ], [
            'id_customer.required' => 'กรุณาเลือกลูกค้า',
            'id_quot.exists'       => 'ใบเสนอราคาที่เลือกไม่ถูกต้อง หรือยังไม่ได้รับการอนุมัติ',
            'name_camp.required'   => 'กรุณาใส่ชื่อแคมป์',
            'status_camp.required' => 'กรุณาเลือกสถานะ',
            'province.required'    => 'กรุณาเลือกจังหวัด',
            'district.required'    => 'กรุณาเลือกอำเภอ',
            'subdistrict.required' => 'กรุณาเลือกตำบล',
            'zipcode.digits'       => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'contact_phone.digits' => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก',
        ]);
    }
}