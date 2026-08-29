<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->q;

        $drivers = Driver::when($q, fn($query) =>
            $query->where('fname_driver', 'like', "%{$q}%")
                ->orWhere('lname_driver', 'like', "%{$q}%")
                ->orWhere('phone_driver', 'like', "%{$q}%")
                ->orWhere('citizenid_driver', 'like', "%{$q}%")
        )
            ->latest('id_driver')
            ->paginate(10)
            ->withQueryString();

        return view('drivers.index', compact('drivers', 'q'));
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateDriver($request);

        if ($request->hasFile('citizen_image')) {
            $data['citizen_image'] = $this->handleImageUpload($request);
        }

        Driver::create($data);

        return redirect()
            ->route('drivers.index')
            ->with('ok', 'เพิ่มข้อมูลพนักงานขับรถเรียบร้อย');
    }

    public function show(Driver $driver)
    {
        return view('drivers.show', compact('driver'));
    }

    public function edit(Driver $driver)
    {
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $this->validateDriver($request, $driver);

        if ($request->hasFile('citizen_image')) {
            $data['citizen_image'] = $this->handleImageUpload($request, $driver->citizen_image);
        }

        $driver->update($data);

        return redirect()
            ->route('drivers.index')
            ->with('ok', 'แก้ไขข้อมูลเรียบร้อย');
    }

    public function destroy(Driver $driver)
    {
        if ($driver->citizen_image && Storage::disk('public')->exists($driver->citizen_image)) {
            Storage::disk('public')->delete($driver->citizen_image);
        }

        $driver->delete();

        return redirect()
            ->route('drivers.index')
            ->with('ok', 'ลบข้อมูลเรียบร้อย');
    }

    private function validateDriver(Request $request, ?Driver $driver = null): array
    {
        $driverId = $driver?->id_driver;

        return $request->validate([
            'fname_driver' => [
                'required',
                'string',
                'max:255',
                Rule::unique('drivers', 'fname_driver')
                    ->where('lname_driver', $request->lname_driver)
                    ->whereNull('deleted_at')
                    ->ignore($driverId, 'id_driver'),
            ],
            'lname_driver'     => ['required', 'string', 'max:255'],
            'address_no'       => ['nullable', 'string', 'max:50'],   // <-- เพิ่ม Validation บ้านเลขที่
            'moo'              => ['nullable', 'string', 'max:20'],   // <-- เพิ่ม Validation หมู่ที่
            'address_detail'   => ['nullable', 'string', 'max:255'],
            'subdistrict'      => ['nullable', 'string', 'max:100'],
            'district'         => ['nullable', 'string', 'max:100'],
            'province'         => ['nullable', 'string', 'max:100'],
            'zipcode'          => ['nullable', 'digits:5'],
            'phone_driver'     => ['nullable', 'digits:10'],
            'citizenid_driver' => [
                'nullable',
                'digits:13',
                Rule::unique('drivers', 'citizenid_driver')->ignore($driverId, 'id_driver'),
            ],
            'citizen_image'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'fname_driver.unique'     => 'ชื่อและนามสกุลนี้ มีอยู่ในระบบแล้ว กรุณาตรวจสอบอีกครั้ง!',
            'citizenid_driver.unique' => 'เลขบัตรประชาชนนี้ ถูกใช้งานไปแล้ว!',
        ]);
    }

    private function handleImageUpload(Request $request, ?string $oldPath = null): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $request->file('citizen_image')->store('citizens', 'public');
    }
}