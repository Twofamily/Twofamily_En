<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TruckStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $yearMax = (int) now()->year;

        return [
            'id_truck' => [
                'bail',
                'required',
                'string',
                'max:45',
                'regex:/^[ก-ฮA-Za-z0-9\s\-]+$/u',
                Rule::unique('trucks', 'id_truck')->whereNull('deleted_at'),
            ],

            'truck_brand_id'    => 'required|exists:truck_brands,id',
            'truck_model_id'    => 'required|exists:truck_models,id',
            'year_truck'        => ['nullable', 'integer', "between:1980,$yearMax"],
            'weight_truck'      => ['nullable', 'integer', 'min:0'],
            'fuelfactory_truck' => ['nullable', 'integer', 'min:0'],
            'cubic_capacity'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status_truck'      => ['bail', 'required', Rule::in(['active', 'maintenance', 'retired'])],

            'province_truck' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/^[\x{0E00}-\x{0E7F}\s]+$/u',
            ],

            'fuel_rate' => [
                'bail',
                'required',
                'numeric',
                'min:0.1',
                'max:50',
            ],

            'title'           => ['required_if:status_truck,maintenance', 'nullable', 'string', 'max:255'],
            'detail'          => ['nullable', 'string', 'max:2000'],
            'garage'          => ['nullable', 'string', 'max:255'],
            'cost'            => ['nullable', 'numeric', 'min:0'],
            'start_date'      => ['required_if:status_truck,maintenance', 'nullable', 'date'],
            'expected_return' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_truck' => trim((string) $this->input('id_truck')),
        ]);
    }

    public function messages(): array
    {
        return [
            'id_truck.regex'  => 'เลขทะเบียนใช้ได้เฉพาะตัวอักษรไทย A-Z ตัวเลข และขีดกลาง (-)',
            'id_truck.unique' => 'เลขทะเบียนนี้มีอยู่ในระบบแล้ว',

            'title.required_if'              => 'กรุณาระบุว่าซ่อมอะไร',
            'start_date.required_if'         => 'กรุณาระบุวันที่เริ่มซ่อม',
            'expected_return.after_or_equal' => 'วันที่คาดว่าเสร็จต้องไม่ก่อนวันที่เริ่มซ่อม',
            'cost.numeric'                   => 'ค่าซ่อมต้องเป็นตัวเลข',
        ];
    }
}
