<?php

namespace App\Http\Controllers;

use App\Models\TruckBrand;
use App\Models\TruckModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TruckModelController extends Controller
{
    public function index(Request $request)
    {
        $q       = $request->q;
        $brandId = $request->brand;

        $models = TruckModel::with('brand')
            ->withCount('trucks')
            ->when($q, fn($x) => $x->where('name_model', 'like', "%$q%"))
            ->when($brandId, fn($x) => $x->where('truck_brand_id', $brandId))
            ->orderBy('truck_brand_id')
            ->orderBy('name_model')
            ->paginate(10);

        $brands = TruckBrand::orderBy('name_brand')->get();

        return view('truck_models.index', compact('models', 'brands', 'q', 'brandId'));
    }

    public function create()
    {
        $model  = new TruckModel();
        $brands = TruckBrand::orderBy('name_brand')->get();

        return view('truck_models.create', compact('model', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        TruckModel::create($data);

        return redirect()->route('truck_models.index')->with('ok', 'เพิ่มรุ่นรถเรียบร้อย');
    }

    public function edit(TruckModel $truck_model)
    {
        $model  = $truck_model;
        $brands = TruckBrand::orderBy('name_brand')->get();

        return view('truck_models.edit', compact('model', 'brands'));
    }

    public function update(Request $request, TruckModel $truck_model)
    {
        $data = $this->validateData($request, $truck_model->id);

        $truck_model->update($data);

        return redirect()->route('truck_models.index')->with('ok', 'แก้ไขรุ่นรถเรียบร้อย');
    }

    public function destroy(TruckModel $truck_model)
    {
        $count = $truck_model->trucks()->count();

        if ($count > 0) {
            return back()->with('error', "ลบไม่ได้ มีรถ {$count} คันใช้รุ่นนี้อยู่");
        }

        $truck_model->delete();

        return back()->with('ok', 'ลบรุ่นรถแล้ว');
    }

    private function validateData(Request $request, $ignoreId = null)
    {
        $yearMax = (int) now()->year + 1;

        $request->merge([
            'name_model' => trim((string) $request->name_model),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return $request->validate([
            'truck_brand_id' => ['required', 'exists:truck_brands,id'],

            'name_model' => [
                'required', 'string', 'max:100',
                // ซ้ำได้ถ้าคนละยี่ห้อหรือคนละปี และไม่นับแถวที่ถูกลบไปแล้ว
                Rule::unique('truck_models', 'name_model')
                    ->where(fn($x) => $x
                        ->where('truck_brand_id', $request->truck_brand_id)
                        ->where('model_year', $request->model_year)
                        ->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],

            'model_year'     => ['required', 'integer', "between:1980,$yearMax"],
            'truck_type'     => ['nullable', 'string', 'max:50'],
            'wheels'         => ['nullable', 'integer', 'min:4', 'max:24'],
            'cubic_capacity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'load_capacity'  => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'curb_weight'    => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'tank_capacity'  => ['nullable', 'integer', 'min:0', 'max:1000'],
            'engine_cc'      => ['nullable', 'integer', 'min:0', 'max:30000'],
            'horsepower'     => ['nullable', 'integer', 'min:0', 'max:1000'],
            'fuel_type'      => ['nullable', 'string', 'max:30'],
            'fuel_rate'      => ['required', 'numeric', 'min:0.1', 'max:50'],
            'is_active'      => ['required', 'boolean'],
        ], [
            'truck_brand_id.required' => 'กรุณาเลือกยี่ห้อ',
            'name_model.required'     => 'กรุณากรอกชื่อรุ่น',
            'name_model.unique'       => 'มีรุ่นนี้ในยี่ห้อและปีนี้อยู่แล้ว',
            'model_year.required'     => 'กรุณาระบุปีรุ่น',
            'fuel_rate.required'      => 'กรุณาระบุอัตราสิ้นเปลือง',
        ]);
    }
}