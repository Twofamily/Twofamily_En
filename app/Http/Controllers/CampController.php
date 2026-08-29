<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $q          = $request->q;
        $status     = $request->status;
        $customerId = $request->customer;

        $camps = Camp::with(['customer', 'quotation'])
            ->withCount(['trucks' => fn($query) => $query->whereNull('released_date')])
            ->when($q, fn($query) => $query->where(fn($x) =>
                $x->where('name_camp', 'like', "%{$q}%")
                  ->orWhere('code_camp', 'like', "%{$q}%")
                  ->orWhere('district', 'like', "%{$q}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name_customer', 'like', "%{$q}%"))
            ))
            ->when($status, fn($query) => $query->where('status_camp', $status))
            ->when($customerId, fn($query) => $query->where('id_customer', $customerId))
            ->latest('id_camp')
            ->paginate(10);

        $customers = Customer::orderBy('name_customer')->get();

        return view('camps.index', compact('camps', 'customers', 'q', 'status', 'customerId'));
    }

    public function create()
    {
        return view('camps.create', [
            'camp'       => new Camp(),
            'customers'  => Customer::orderBy('name_customer')->get(),
            'quotations' => $this->quotationOptions(),
            'isEdit'     => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCamp($request);

        if ($error = $this->checkQuotationMatch($data)) {
            return back()->withInput()->with('error', $error);
        }

        $data['code_camp'] = Camp::generateCode();

        $camp = Camp::create($data);

        return redirect()->route('camps.show', $camp)->with('ok', 'เพิ่มแคมป์เรียบร้อย');
    }

    public function edit(Camp $camp)
    {
        return view('camps.edit', [
            'camp'       => $camp,
            'customers'  => Customer::orderBy('name_customer')->get(),
            'quotations' => $this->quotationOptions($camp),
            'isEdit'     => true,
        ]);
    }

    public function update(Request $request, Camp $camp)
    {
        $data = $this->validateCamp($request, $camp);

        if ($error = $this->checkQuotationMatch($data)) {
            return back()->withInput()->with('error', $error);
        }

        $camp->update($data);

        return redirect()->route('camps.show', $camp)->with('ok', 'แก้ไขแคมป์เรียบร้อย');
    }

    public function destroy(Camp $camp)
    {
        $count = $camp->trucks()->wherePivotNull('released_date')->count();

        if ($count > 0) {
            return back()->with('error', "ลบไม่ได้ มีรถประจำแคมป์นี้อยู่ {$count} คัน");
        }

        $camp->delete();

        return redirect()->route('camps.index')->with('ok', 'ลบแคมป์แล้ว');
    }

    public function show(Camp $camp)
    {
        $camp->load([
            'customer',
            'quotation.details.product',
            'trucks' => fn($q) => $q->orderByPivot('assigned_date', 'desc'),
        ]);

        $assignedIds = $camp->trucks()->wherePivotNull('released_date')->pluck('trucks.id_truck');

        $availableTrucks = Truck::available()
            ->whereNotIn('id_truck', $assignedIds)
            ->orderBy('id_truck')
            ->get();

        $deliveryNotes = $camp->deliveryNotes()
            ->with('invoice')
            ->latest('delivery_date')
            ->get();

        $progress = $this->buildProgress($camp);

        return view('camps.show', compact('camp', 'availableTrucks', 'deliveryNotes', 'progress'));
    }

    /** เทียบยอดตามใบเสนอราคา กับยอดที่ส่งจริงสะสม */
    private function buildProgress(Camp $camp)
    {
        if (! $camp->quotation) {
            return collect();
        }

        $delivered = \App\Models\DeliveryNoteDetail::whereIn(
                'id_delivery_note',
                $camp->deliveryNotes()->pluck('id_delivery_note')
            )
            ->selectRaw('id_product, SUM(quantity) as qty')
            ->groupBy('id_product')
            ->pluck('qty', 'id_product');

        return $camp->quotation->details->map(function ($detail) use ($delivered) {
            $ordered = (float) $detail->quantity;
            $done    = (float) ($delivered[$detail->id_product] ?? 0);

            return [
                'name_product' => $detail->product->name_product ?? '-',
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
            'assigned_date' => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
        ], [
            'id_truck.exists' => 'รถคันนี้ไม่พร้อมใช้งาน',
        ]);

        $alreadyHere = $camp->trucks()
            ->wherePivot('id_truck', $data['id_truck'])
            ->wherePivotNull('released_date')
            ->exists();

        if ($alreadyHere) {
            return back()->with('warning', 'รถคันนี้ประจำแคมป์นี้อยู่แล้ว');
        }

        $camp->trucks()->attach($data['id_truck'], [
            'assigned_date' => $data['assigned_date'],
            'note'          => $data['note'] ?? null,
        ]);

        return back()->with('ok', "เพิ่มรถ {$data['id_truck']} เข้าแคมป์แล้ว");
    }

    public function releaseTruck(Request $request, Camp $camp, $assignmentId)
    {
        $data = $request->validate([
            'released_date' => ['required', 'date'],
        ]);

        $camp->trucks()->wherePivot('id_assignment', $assignmentId)->updateExistingPivot(
            $camp->trucks()->wherePivot('id_assignment', $assignmentId)->first()->id_truck ?? null,
            ['released_date' => $data['released_date']]
        );

        return back()->with('ok', 'ถอนรถออกจากแคมป์แล้ว');
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
            'subdistrict'    => ['nullable', 'string', 'max:100'],
            'district'       => ['nullable', 'string', 'max:100'],
            'province'       => ['nullable', 'string', 'max:100'],
            'zipcode'        => ['nullable', 'digits:5'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
            'contact_name'   => ['nullable', 'string', 'max:255'],
            'contact_phone'  => ['nullable', 'digits:10'],
            'status_camp'    => ['required', 'in:active,closed'],
            'note'           => ['nullable', 'string', 'max:1000'],
        ], [
            'id_customer.required' => 'กรุณาเลือกลูกค้า',
            'id_quot.exists'       => 'ใบเสนอราคาที่เลือกไม่ถูกต้อง หรือยังไม่ได้รับการอนุมัติ',
            'name_camp.required'   => 'กรุณากรอกชื่อแคมป์',
            'zipcode.digits'       => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'contact_phone.digits' => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก',
        ]);
    }
}