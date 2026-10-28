<?php

namespace App\Http\Controllers;

use App\Models\TruckBrand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckBrandController extends Controller
{
    /**
     * ยี่ห้อเป็นแท็บหนึ่งในหน้ารถบรรทุกแล้ว → ส่งไปแท็บนั้น (พกคำค้นหาไปด้วย)
     * ยกเว้นคนที่ไม่มีสิทธิ์ดูหน้ารถบรรทุก → ใช้หน้าเดิม
     */
    public function index(Request $request)
    {
        if ($request->user()?->canViewModule('trucks')) {
            return redirect()->route('trucks.index', ['tab' => 'brands'] + $request->query());
        }

        return view('truck_brands.index', $this->listData($request));
    }

    /** ข้อมูลตารางยี่ห้อ — ใช้ทั้งแท็บในหน้ารถบรรทุก และหน้าเดิม */
    public function listData(Request $request): array
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        $brands = TruckBrand::withCount('models')
            ->when($search !== '', fn ($query) => $query->where('name_brand', 'like', "%{$search}%"))
            ->orderBy('name_brand')
            ->paginate($perPage)
            ->withQueryString();

        return compact('brands', 'search', 'perPage');
    }

    /** ฟอร์มเพิ่มยี่ห้อย้ายไปเป็น modal ในแท็บยี่ห้อแล้ว */
    public function create(Request $request)
    {
        if ($request->user()?->canViewModule('trucks')) {
            return redirect()->route('trucks.index', ['tab' => 'brands', 'open' => 'brand']);
        }

        $brand = new TruckBrand();

        return view('truck_brands.create', compact('brand'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        TruckBrand::create($data);

        return redirect()
            ->route('trucks.index', ['tab' => 'brands'])
            ->with('ok', 'เพิ่มยี่ห้อเรียบร้อย');
    }

    public function edit(TruckBrand $truckBrand)
    {
        $brand = $truckBrand;

        return view('truck_brands.edit', compact('brand'));
    }

    public function update(Request $request, TruckBrand $truckBrand)
    {
        $data = $this->validateData($request, $truckBrand->getKey());

        $truckBrand->update($data);

        return redirect()
            ->route('trucks.index', ['tab' => 'brands'])
            ->with('ok', 'แก้ไขยี่ห้อเรียบร้อย');
    }

    public function destroy(TruckBrand $truckBrand)
    {
        if ($truckBrand->models()->exists()) {
            $count = $truckBrand->models()->count();
            return back()->with('error', "ไม่สามารถลบได้ เนื่องจากยี่ห้อนี้มีรุ่นรถผูกไว้อยู่ {$count} รุ่น");
        }

        $truckBrand->delete();

        return back()->with('ok', 'ลบยี่ห้อเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, mixed $ignoreId = null): array
    {
        $request->merge([
            'name_brand' => mb_strtoupper(trim((string) $request->name_brand)),
        ]);

        return $request->validate([
            'name_brand' => [
                'required',
                'string',
                'max:100',
                Rule::unique('truck_brands', 'name_brand')
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
        ], [
            'name_brand.required' => 'กรุณาใส่ชื่อยี่ห้อ',
            'name_brand.max'      => 'ชื่อยี่ห้อยาวได้ไม่เกิน 100 ตัวอักษร',
            'name_brand.unique'   => 'มียี่ห้อนี้ในระบบแล้ว',
        ]);
    }
}