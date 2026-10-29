<?php

namespace App\Http\Controllers;

use App\Models\TruckBrand;
use App\Models\TruckModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckModelController extends Controller
{
    /**
     * รุ่นรถเป็นแท็บหนึ่งในหน้ารถบรรทุกแล้ว → ส่งไปแท็บนั้น (พกตัวกรองไปด้วย)
     * ยกเว้นคนที่ไม่มีสิทธิ์ดูหน้ารถบรรทุก → ใช้หน้าเดิม
     */
    public function index(Request $request)
    {
        if ($request->user()?->canViewModule('trucks')) {
            return redirect()->route('trucks.index', ['tab' => 'models'] + $request->query());
        }

        return view('truck_models.index', $this->listData($request));
    }

    /** ข้อมูลตารางรุ่นรถ — ใช้ทั้งแท็บในหน้ารถบรรทุก และหน้าเดิม */
    public function listData(Request $request): array
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        // dropdown จำนวนล้อ: ตัวเลือก = จำนวนล้อที่มีอยู่จริงในตาราง เช่น [6 => '6 ล้อ', 10 => '10 ล้อ']
        $wheelLabels = TruckModel::whereNotNull('wheels')->distinct()->orderBy('wheels')->pluck('wheels')
            ->mapWithKeys(fn ($w) => [$w => "{$w} ล้อ"])
            ->all();
        $wheels = $this->statusFilter($request, array_map('strval', array_keys($wheelLabels)), 'wheels');

        // ค้นหาแบบช่วงของคอลัมน์ ปี / ล้อ / คิว / กม.ต่อลิตร
        // ตัวเลือก dropdown = ค่าที่มีอยู่จริงในตาราง (ไม่ต้องพิมพ์เอง)
        $optionsOf = fn (string $col) => TruckModel::whereNotNull($col)->distinct()->orderBy($col)->pluck($col);

        $ranges = $this->numberRanges($request, [
            'year'   => ['label' => 'ปีรุ่น',          'unit' => null,       'options' => $optionsOf('model_year')],
            'wheels' => ['label' => 'จำนวนล้อ',        'unit' => 'ล้อ',      'options' => $optionsOf('wheels')],
            'cubic'  => ['label' => 'ความจุกระบะ',     'unit' => 'คิว',      'options' => $optionsOf('cubic_capacity')],
            'rate'   => ['label' => 'อัตราสิ้นเปลือง', 'unit' => 'กม./ลิตร', 'options' => $optionsOf('fuel_rate')],
        ]);

        // ช่องค้นหาช่องเดียว: ชื่อรุ่น ยี่ห้อ ประเภท เชื้อเพลิง ปีรุ่น
        // trucks: ใช้แสดงรายชื่อรถในหน้าต่าง "ดู" ของแต่ละรุ่น
        $models = TruckModel::with(['brand', 'trucks:id_truck,truck_model_id,status_truck'])
            ->withCount('trucks')
            ->when($wheels !== null, fn ($query) => $query->where('wheels', (int) $wheels))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'model_year', $ranges['year']))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'wheels', $ranges['wheels']))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'cubic_capacity', $ranges['cubic']))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'fuel_rate', $ranges['rate']))
            ->when($search !== '', fn ($query) => $query->where(fn ($sub) =>
                $sub->where('name_model', 'like', "%{$search}%")
                    ->orWhere('truck_type', 'like', "%{$search}%")
                    ->orWhere('fuel_type', 'like', "%{$search}%")
                    ->orWhere('model_year', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name_brand', 'like', "%{$search}%"))
            ))
            ->orderBy('truck_brand_id')
            ->orderBy('name_model')
            ->paginate($perPage)
            ->withQueryString();

        return compact('models', 'search', 'perPage', 'wheels', 'wheelLabels', 'ranges');
    }

    /** ฟอร์มเพิ่มรุ่นย้ายไปเป็น modal ในแท็บรุ่นรถแล้ว */
    public function create(Request $request)
    {
        if ($request->user()?->canViewModule('trucks')) {
            return redirect()->route('trucks.index', ['tab' => 'models', 'open' => 'model']);
        }

        $model  = new TruckModel();
        $brands = TruckBrand::orderBy('name_brand')->get();

        return view('truck_models.create', compact('model', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        TruckModel::create($data);

        return redirect()
            ->route('trucks.index', ['tab' => 'models'])
            ->with('ok', 'เพิ่มรุ่นรถเรียบร้อย');
    }

    /** ฟอร์มแก้ไขรุ่นย้ายไปเป็น modal ในแท็บรุ่นรถแล้ว — เปิด URL เดิมก็เด้ง modal ให้ */
    public function edit(Request $request, TruckModel $truck_model)
    {
        if ($request->user()?->canViewModule('trucks')) {
            return redirect()->route('trucks.index', ['tab' => 'models', 'edit' => $truck_model->getKey()]);
        }

        $model  = $truck_model;
        $brands = TruckBrand::orderBy('name_brand')->get();

        return view('truck_models.edit', compact('model', 'brands'));
    }

    public function update(Request $request, TruckModel $truck_model)
    {
        // error ของ modal แก้ไขแยกไว้ใน bag "updateModel" ไม่ปนกับ modal เพิ่ม
        $data = $this->validateData($request, $truck_model->getKey(), 'updateModel');

        $truck_model->update($data);

        // กลับไปหน้าเดิม พร้อมคำค้นหา / ตัวกรอง / เลขหน้าที่ใช้อยู่
        $keep = array_filter($request->only('page', 'search', 'wheels', 'per_page'), fn ($v) => $v !== null && $v !== '');

        return redirect()
            ->route('trucks.index', ['tab' => 'models'] + $keep)
            ->with('ok', 'แก้ไขรุ่นรถเรียบร้อย');
    }

    public function destroy(TruckModel $truck_model)
    {
        if ($truck_model->trucks()->exists()) {
            $count = $truck_model->trucks()->count();
            return back()->with('error', "ไม่สามารถลบได้ เนื่องจากมีรถ {$count} คันใช้งานรุ่นนี้อยู่");
        }

        $truck_model->delete();

        return back()->with('ok', 'ลบรุ่นรถเรียบร้อยแล้ว');
    }

    private function validateData(Request $request, mixed $ignoreId = null, string $errorBag = 'default'): array
    {
        $yearMax = (int) now()->year + 1;
        $brandKey = (new TruckBrand())->getKeyName();

        $request->merge([
            'name_model' => trim((string) $request->name_model),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return $request->validateWithBag($errorBag, [
            'truck_brand_id' => ['required', Rule::exists('truck_brands', $brandKey)],

            'name_model' => [
                'required',
                'string',
                'max:100',
                Rule::unique('truck_models', 'name_model')
                    ->where(fn($query) => $query
                        ->where('truck_brand_id', $request->truck_brand_id)
                        ->where('model_year', $request->model_year)
                        ->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],

            'model_year'     => ['required', 'integer', "between:1980,{$yearMax}"],
            'truck_type'     => ['required', 'string', 'max:50'],
            'wheels'         => ['required', 'integer', 'min:4', 'max:24'],
            'cubic_capacity' => ['required', 'numeric', 'min:0', 'max:100'],
            'load_capacity'  => ['required', 'numeric', 'min:0', 'max:100000'],
            'curb_weight'    => ['required', 'numeric', 'min:0', 'max:100000'],
            'tank_capacity'  => ['required', 'integer', 'min:0', 'max:1000'],
            'engine_cc'      => ['required', 'integer', 'min:0', 'max:30000'],
            'horsepower'     => ['required', 'integer', 'min:0', 'max:1000'],
            'fuel_type'      => ['required', 'string', 'max:30'],
            'fuel_rate'      => ['required', 'numeric', 'min:0.1', 'max:50'],
            'is_active'      => ['required', 'boolean'],
        ], [
            'truck_brand_id.required' => 'กรุณาเลือกยี่ห้อ',
            'name_model.required'     => 'กรุณากรอกชื่อรุ่น',
            'name_model.unique'       => 'มีรุ่นนี้ในยี่ห้อและปีนี้อยู่แล้ว',
            'model_year.required'     => 'กรุณาระบุปีรุ่น',
            'fuel_rate.required'      => 'กรุณาระบุอัตราสิ้นเปลือง',
            'truck_type.required'     => 'กรุณาเลือกประเภทรถ',
            'wheels.required'         => 'กรุณากรอกจำนวนล้อ',
            'cubic_capacity.required' => 'กรุณากรอกความจุกระบะ',
            'load_capacity.required'  => 'กรุณากรอกน้ำหนักบรรทุก',
            'curb_weight.required'    => 'กรุณากรอกน้ำหนักรถเปล่า',
            'tank_capacity.required'  => 'กรุณากรอกความจุถังน้ำมัน',
            'engine_cc.required'      => 'กรุณากรอกขนาดเครื่องยนต์',
            'horsepower.required'     => 'กรุณากรอกแรงม้า',
            'fuel_type.required'      => 'กรุณากรอกชนิดเชื้อเพลิง',
        ]);
    }
}