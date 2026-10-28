<?php

namespace App\Http\Controllers;

use App\Http\Requests\TruckStoreRequest;
use App\Http\Requests\TruckUpdateRequest;
use App\Models\Truck;
use App\Models\TruckBrand;
use App\Models\TruckMaintenance;
use App\Models\TruckModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TruckController extends Controller
{
    /** แท็บในหน้ารถบรรทุก => [ชื่อ, ไอคอน, สิทธิ์ที่ต้องมี] */
    private const TABS = [
        'trucks' => ['label' => 'รถบรรทุก', 'icon' => 'bi-truck-front', 'module' => 'trucks'],
        'models' => ['label' => 'รุ่นรถ',    'icon' => 'bi-card-list',   'module' => 'truck_models'],
        'brands' => ['label' => 'ยี่ห้อ',     'icon' => 'bi-award',       'module' => 'truck_brands'],
    ];

    /**
     * หน้ารถบรรทุก มี 3 แท็บ (?tab=)
     *   trucks : รถบรรทุกในบริษัท (ค่าเริ่มต้น)
     *   models : รุ่นรถ — เห็นเฉพาะคนที่มีสิทธิ์ดูหน้ารุ่นรถ
     *   brands : ยี่ห้อ — เห็นเฉพาะคนที่มีสิทธิ์ดูหน้ายี่ห้อ
     *
     * ปุ่มเพิ่มของทุกแท็บเปิดเป็น modal ในหน้านี้ ไม่ย้ายหน้า
     * query เฉพาะแท็บที่เปิดอยู่ แท็บอื่นนับแค่จำนวนไว้โชว์บนป้าย
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $tabs = collect(self::TABS)
            ->filter(fn ($t) => $user?->canViewModule($t['module']))
            ->all();

        $tab = array_key_exists((string) $request->query('tab'), $tabs) ? $request->query('tab') : 'trucks';

        $counts = [
            'trucks' => Truck::count(),
            'models' => TruckModel::count(),
            'brands' => TruckBrand::count(),
        ];

        $data = match ($tab) {
            'models' => app(TruckModelController::class)->listData($request),
            'brands' => app(TruckBrandController::class)->listData($request),
            default  => $this->truckListData($request),
        };

        // dropdown ใน modal เพิ่มรถ (ยี่ห้อ → รุ่น → สเปค) และ modal เพิ่มรุ่น
        $brandOptions = match ($tab) {
            'trucks' => TruckBrand::with('models')->orderBy('name_brand')->get(),
            'models' => TruckBrand::orderBy('name_brand')->get(),
            default  => collect(),
        };

        return view('trucks.index', $data + compact('tab', 'tabs', 'counts', 'brandOptions'));
    }

    /** ข้อมูลตารางรถบรรทุก (แท็บแรก) */
    private function truckListData(Request $request): array
    {
        $perPage = $this->perPage($request);
        $search  = $this->searchTerm($request);

        // พิมพ์ "ซ่อม" / "พร้อม" / "ปลด" ก็ค้นตามสถานะได้
        $statuses = $this->statusesMatching($search, Truck::STATUS_LABELS);

        // dropdown เลือกสถานะ + ช่วงปีที่ซื้อ
        $status = $this->statusFilter($request, array_keys(Truck::STATUS_LABELS));
        $ranges = $this->numberRanges($request, [
            'year' => [
                'label'   => 'ปีที่ซื้อ',
                'unit'    => null,
                'options' => Truck::whereNotNull('year_truck')->distinct()->orderBy('year_truck')->pluck('year_truck'),
            ],
        ]);

        // ช่องค้นหาช่องเดียว: ทะเบียน จังหวัด ปี ยี่ห้อ รุ่น และสถานะ
        $trucks = Truck::with(['brand', 'model'])
            ->when($search !== '', function ($query) use ($search, $statuses) {
                $query->where(function ($sub) use ($search, $statuses) {
                    $sub->where('id_truck', 'like', "%{$search}%")
                        ->orWhere('province_truck', 'like', "%{$search}%")
                        ->orWhere('year_truck', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($b) => $b->where('name_brand', 'like', "%{$search}%"))
                        ->orWhereHas('model', fn ($m) => $m->where('name_model', 'like', "%{$search}%"));
                    if ($statuses) {
                        $sub->orWhereIn('status_truck', $statuses);
                    }
                });
            })
            ->when($status, fn ($query) => $query->where('status_truck', $status))
            ->tap(fn ($query) => $this->applyNumberRange($query, 'year_truck', $ranges['year']))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return compact('trucks', 'search', 'perPage', 'status', 'ranges');
    }

    /** ฟอร์มเพิ่มรถย้ายไปเป็น modal ในหน้ารายการแล้ว — เปิด URL เดิมก็เด้ง modal ให้ */
    public function create()
    {
        return redirect()->route('trucks.index', ['open' => 'truck']);
    }

    public function store(TruckStoreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('trucks', 'public');
        }

        DB::transaction(function () use ($data, $request) {
            $truck = Truck::create($data);

            if ($data['status_truck'] === 'maintenance') {
                TruckMaintenance::create(array_merge(
                    ['id_truck' => $truck->id_truck],
                    $this->extractMaintenanceData($request->all())
                ));
            }
        });

        return redirect()
            ->route('trucks.index')
            ->with('ok', 'เพิ่มรถเรียบร้อย');
    }

    public function show(Truck $truck)
    {
        $truck->load(['brand', 'model', 'maintenances', 'ongoingMaintenance']);

        return view('trucks.show', compact('truck'));
    }

    public function edit(Truck $truck)
    {
        $brands = TruckBrand::with('models')->orderBy('name_brand')->get();

        return view('trucks.edit', compact('truck', 'brands'));
    }

    public function update(TruckUpdateRequest $request, Truck $truck)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImageIfExists($truck->image);
            $data['image'] = $request->file('image')->store('trucks', 'public');
        }

        $truck->update($data);

        return redirect()
            ->route('trucks.index')
            ->with('ok', 'อัปเดตรถเรียบร้อย');
    }

    public function destroy(Truck $truck)
    {
        DB::transaction(function () use ($truck) {
            $this->deleteImageIfExists($truck->image);
            $truck->delete();
        });

        return redirect()
            ->route('trucks.index')
            ->with('ok', 'ลบข้อมูลรถแล้ว');
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
            return back()->with('ok', 'สถานะไม่มีการเปลี่ยนแปลง');
        }

        DB::transaction(function () use ($truck, $data) {
            if ($truck->status_truck === 'maintenance') {
                $truck->maintenances()
                    ->whereNull('finished_date')
                    ->update(['finished_date' => now()->toDateString()]);
            }

            if ($data['status_truck'] === 'maintenance') {
                TruckMaintenance::create(array_merge(
                    ['id_truck' => $truck->id_truck],
                    $this->extractMaintenanceData($data)
                ));
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
        $startDate = $maintenance->start_date ? $maintenance->start_date->toDateString() : now()->toDateString();

        $data = $request->validate([
            'finished_date' => ['required', 'date', 'after_or_equal:' . $startDate],
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

    private function deleteImageIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function extractMaintenanceData(array $input): array
    {
        return [
            'title'           => $input['title'] ?? null,
            'detail'          => $input['detail'] ?? null,
            'garage'          => $input['garage'] ?? null,
            'cost'            => $input['cost'] ?? null,
            'start_date'      => $input['start_date'] ?? now()->toDateString(),
            'expected_return' => $input['expected_return'] ?? null,
        ];
    }
}