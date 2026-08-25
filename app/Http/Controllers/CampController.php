<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Truck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampController extends Controller
{
    public function index(Request $request)
    {
        $q          = $request->q;
        $status     = $request->status;
        $customerId = $request->customer;

        $camps = Camp::with('customer')
            ->withCount(['trucks' => fn($q) => $q->whereNull('released_date')])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($x) use ($q) {
                    $x->where('name_camp', 'like', "%$q%")
                        ->orWhere('code_camp', 'like', "%$q%")
                        ->orWhere('district', 'like', "%$q%")
                        ->orWhereHas('customer', fn($c) => $c->where('name_customer', 'like', "%$q%"));
                });
            })
            ->when($status, fn($query) => $query->where('status_camp', $status))
            ->when($customerId, fn($query) => $query->where('id_customer', $customerId))
            ->latest('id_camp')
            ->paginate(10);

        $customers = Customer::orderBy('name_customer')->get();

        return view('camps.index', compact('camps', 'customers', 'q', 'status', 'customerId'));
    }

    public function create()
    {
        $camp      = new Camp();
        $customers = Customer::orderBy('name_customer')->get();

        return view('camps.create', compact('camp', 'customers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCamp($request);

        $data['code_camp'] = Camp::generateCode();

        Camp::create($data);

        return redirect()->route('camps.index')->with('ok', 'เพิ่มแคมป์เรียบร้อย');
    }

    public function edit(Camp $camp)
    {
        $customers = Customer::orderBy('name_customer')->get();

        return view('camps.edit', compact('camp', 'customers'));
    }

    public function update(Request $request, Camp $camp)
    {
        // code_camp ไม่อยู่ใน validateCamp จึงไม่มีทางถูกแก้
        $camp->update($this->validateCamp($request));

        return redirect()->route('camps.index')->with('ok', 'แก้ไขแคมป์เรียบร้อย');
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

    // ใช้ร่วมกันทั้ง store และ update
    private function validateCamp(Request $request): array
    {
        return $request->validate([
            'id_customer'    => ['required', 'exists:customers,id_customer'],
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
            'name_camp.required'   => 'กรุณากรอกชื่อแคมป์',
            'zipcode.digits'       => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'contact_phone.digits' => 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก',
        ]);
    }

    public function show(Camp $camp)
    {
        $camp->load(['customer', 'trucks' => fn($q) => $q->orderByPivot('assigned_date', 'desc')]);

        // รถที่ว่างอยู่ = พร้อมใช้งาน และยังไม่ได้ประจำแคมป์นี้
        $assignedIds = $camp->trucks()->wherePivotNull('released_date')->pluck('trucks.id_truck');

        $availableTrucks = Truck::available()
            ->whereNotIn('id_truck', $assignedIds)
            ->orderBy('id_truck')
            ->get();

        return view('camps.show', compact('camp', 'availableTrucks'));
    }

    // เพิ่มรถเข้าแคมป์
    public function assignTruck(Request $request, Camp $camp)
    {
        $data = $request->validate([
            'id_truck'      => [
                'required',
                Rule::exists('trucks', 'id_truck')->where('status_truck', 'active'),
            ],
            'assigned_date' => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
        ], [
            'id_truck.exists' => 'รถคันนี้ไม่พร้อมใช้งาน',
        ]);

        // กันเพิ่มซ้ำถ้ารถคันนี้ยังทำงานที่แคมป์นี้อยู่
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

        DB::table('camp_truck')
            ->where('id_assignment', $assignmentId)
            ->where('id_camp', $camp->id_camp)
            ->update([
                'released_date' => $data['released_date'],
                'updated_at'    => now(),
            ]);

        return back()->with('ok', 'ถอนรถออกจากแคมป์แล้ว');
    }
}
