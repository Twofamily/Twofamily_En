<?php

namespace App\Http\Controllers;

use App\Models\TruckBrand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckBrandController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;

        $brands = TruckBrand::withCount('models')
            ->when($q, fn($x) => $x->where('name_brand', 'like', "%$q%"))
            ->orderBy('name_brand')
            ->paginate(10);

        return view('truck_brands.index', compact('brands', 'q'));
    }

    public function create()
    {
        $brand = new TruckBrand();
        return view('truck_brands.create', compact('brand'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        TruckBrand::create($data);

        return redirect()->route('truck_brands.index')->with('ok', 'เพิ่มยี่ห้อเรียบร้อย');
    }

    public function edit(TruckBrand $truckBrand)
    {
        $brand = $truckBrand;
        return view('truck_brands.edit', compact('brand'));
    }

    public function update(Request $request, TruckBrand $truckBrand)
    {
        $data = $this->validateData($request, $truckBrand->id);

        $truckBrand->update($data);

        return redirect()->route('truck_brands.index')->with('ok', 'แก้ไขยี่ห้อเรียบร้อย');
    }

    public function destroy(TruckBrand $truckBrand)
    {
        // กันลบยี่ห้อที่ยังมีรุ่นอยู่
        $count = $truckBrand->models()->count();

        if ($count > 0) {
            return back()->with('error', "ลบไม่ได้ ยี่ห้อนี้มีรุ่นรถอยู่ {$count} รุ่น");
        }

        $truckBrand->delete();

        return back()->with('ok', 'ลบยี่ห้อแล้ว');
    }

    private function validateData(Request $request, $ignoreId = null)
    {
        // ตัดช่องว่างหน้า-หลัง และแปลงเป็นตัวพิมพ์ใหญ่ให้เหมือนกันทั้งระบบ
        $request->merge([
            'name_brand' => mb_strtoupper(trim((string) $request->name_brand)),
        ]);

        return $request->validate([
            'name_brand' => [
                'required', 'string', 'max:100',
                // เช็คซ้ำเฉพาะแถวที่ยังไม่ถูกลบ (soft delete)
                Rule::unique('truck_brands', 'name_brand')
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
        ], [
            'name_brand.required' => 'กรุณากรอกชื่อยี่ห้อ',
            'name_brand.unique'   => 'มียี่ห้อนี้ในระบบแล้ว',
        ]);
    }
}