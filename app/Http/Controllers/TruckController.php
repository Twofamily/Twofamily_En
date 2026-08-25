<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckBrand;
use App\Models\TruckMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\TruckStoreRequest;
use App\Http\Requests\TruckUpdateRequest;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;
        $status = $request->status;

        $query = Truck::with(['brand', 'model']);

        if ($q) {
            $query->where(function ($x) use ($q) {
                $x->where('id_truck', 'like', "%$q%")
                    ->orWhereHas('brand', function ($b) use ($q) {
                        $b->where('name_brand', 'like', "%$q%");
                    })
                    ->orWhereHas('model', function ($m) use ($q) {
                        $m->where('name_model', 'like', "%$q%");
                    });
            });
        }

        if ($status) {
            $query->where('status_truck', $status);
        }

        $trucks = $query->orderByDesc('created_at')->paginate(10);

        return view('trucks.index', compact('trucks', 'q', 'status'));
    }

    public function create()
    {
        $truck = new Truck();
        $brands = TruckBrand::with('models')->orderBy('name_brand')->get();
        return view('trucks.create', compact('truck', 'brands'));
    }

    public function store(TruckStoreRequest $request)
    {
        $data = $request->validated();

        // จัดการไฟล์รูปภาพ
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('trucks', 'public');
        }

        DB::transaction(function () use ($data, $request) {
            $truck = Truck::create($data);

            if ($data['status_truck'] === 'maintenance') {
                TruckMaintenance::create([
                    'id_truck'        => $truck->id_truck,
                    'title'           => $request->title,
                    'detail'          => $request->detail,
                    'garage'          => $request->garage,
                    'cost'            => $request->cost,
                    'start_date'      => $request->start_date,
                    'expected_return' => $request->expected_return,
                ]);
            }
        });

        return redirect()->route('trucks.index')->with('ok', 'เพิ่มรถเรียบร้อย');
    }

    public function edit($id)
    {
        $truck = Truck::findOrFail($id);
        $brands = TruckBrand::with('models')->orderBy('name_brand')->get();
        return view('trucks.edit', compact('truck', 'brands'));
    }

    public function update(TruckUpdateRequest $request, Truck $truck)
    {
        $data = $request->validated();

        // จัดการไฟล์รูปภาพกรณีมีการอัปโหลดใหม่
        if ($request->hasFile('image')) {
            // ลบรูปภาพเก่าออกก่อนถ้ามี
            if ($truck->image && Storage::disk('public')->exists($truck->image)) {
                Storage::disk('public')->delete($truck->image);
            }

            // บันทึกรูปภาพใหม่
            $data['image'] = $request->file('image')->store('trucks', 'public');
        }

        $truck->update($data);

        return redirect()->route('trucks.index')->with('ok', 'อัปเดตรถเรียบร้อย');
    }

    public function destroy(Truck $truck)
    {
        // ลบไฟล์รูปภาพออกจาก Storage
        if ($truck->image && Storage::disk('public')->exists($truck->image)) {
            Storage::disk('public')->delete($truck->image);
        }

        $truck->delete();
        return redirect()->route('trucks.index')->with('ok', 'ลบข้อมูลรถแล้ว');
    }

    public function show(Truck $truck)
    {
        $truck->load(['brand', 'model', 'maintenances', 'ongoingMaintenance']);

        return view('trucks.show', compact('truck'));
    }

    public function updateStatus(Request $request, Truck $truck)
    {
        $data = $request->validate([
            'status_truck'    => ['required', 'in:active,maintenance,retired'],

            'title'           => ['required_if:status_truck,maintenance', 'nullable', 'string', 'max:255'],
            'detail'          => ['nullable', 'string', 'max:2000'],
            'garage'          => ['nullable', 'string', 'max:255'],
            'cost'            => ['nullable', 'numeric', 'min:0'],
            'start_date'      => ['required_if:status_truck,maintenance', 'nullable', 'date'],
            'expected_return' => ['nullable', 'date', 'after_or_equal:start_date'],

            'retire_reason'   => ['required_if:status_truck,retired', 'nullable', 'string', 'max:500'],
        ], [
            'title.required_if'              => 'กรุณาระบุว่าซ่อมอะไร',
            'start_date.required_if'         => 'กรุณาระบุวันที่เริ่มซ่อม',
            'retire_reason.required_if'      => 'กรุณาระบุเหตุผลที่ปลดประจำการ',
            'expected_return.after_or_equal' => 'วันที่คาดว่าเสร็จต้องไม่ก่อนวันที่เริ่มซ่อม',
            'cost.numeric'                   => 'ค่าซ่อมต้องเป็นตัวเลข',
        ]);

        if ($truck->status_truck === $data['status_truck']) {
            return back()->with('info', 'สถานะไม่มีการเปลี่ยนแปลง');
        }

        DB::transaction(function () use ($truck, $data) {

            if ($truck->status_truck === 'maintenance') {
                $truck->maintenances()
                    ->whereNull('finished_date')
                    ->update(['finished_date' => now()->toDateString()]);
            }

            if ($data['status_truck'] === 'maintenance') {
                TruckMaintenance::create([
                    'id_truck'        => $truck->id_truck,
                    'title'           => $data['title'],
                    'detail'          => $data['detail'] ?? null,
                    'garage'          => $data['garage'] ?? null,
                    'cost'            => $data['cost'] ?? null,
                    'start_date'      => $data['start_date'],
                    'expected_return' => $data['expected_return'] ?? null,
                ]);
            }

            if ($data['status_truck'] === 'retired') {
                TruckMaintenance::create([
                    'id_truck'      => $truck->id_truck,
                    'title'         => 'ปลดประจำการ',
                    'detail'        => $data['retire_reason'],
                    'start_date'    => now()->toDateString(),
                    'finished_date' => now()->toDateString(),
                ]);
            }

            $truck->update(['status_truck' => $data['status_truck']]);
        });

        return back()->with('ok', "เปลี่ยนสถานะรถ {$truck->id_truck} เป็น \"{$truck->fresh()->status_label}\" แล้ว");
    }

    public function finishMaintenance(Request $request, TruckMaintenance $maintenance)
    {
        $data = $request->validate([
            'finished_date' => ['required', 'date', 'after_or_equal:' . $maintenance->start_date->toDateString()],
            'cost'          => ['nullable', 'numeric', 'min:0'],
        ], [
            'finished_date.after_or_equal' => 'วันที่ซ่อมเสร็จต้องไม่ก่อนวันที่เริ่มซ่อม',
        ]);

        DB::transaction(function () use ($maintenance, $data) {
            $maintenance->update([
                'finished_date' => $data['finished_date'],
                'cost'          => $data['cost'] ?? $maintenance->cost,
            ]);

            $maintenance->truck->update(['status_truck' => 'active']);
        });

        return back()->with('ok', 'ปิดงานซ่อมแล้ว รถกลับมาพร้อมใช้งาน');
    }
}