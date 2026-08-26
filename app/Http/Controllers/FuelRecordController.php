<?php

namespace App\Http\Controllers;

use App\Models\Camp;
use App\Models\FuelRecord;
use App\Models\Truck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FuelRecordController extends Controller
{
    /**
     * เมื่อบรรทุกเต็มความจุ ประสิทธิภาพ กม./ลิตร ลดลง 20%
     */
    private const FULL_LOAD_EFFICIENCY_LOSS = 0.20;

    public function index(Request $request): View
    {
        $q = trim(
            (string) $request->input('q', '')
        );

        $trucks_id = trim(
            (string) $request->input('trucks_id', '')
        );

        $date_from = $request->input('date_from');

        $date_to = $request->input('date_to');

        $records = FuelRecord::query()
            ->with([
                'truck.brand',
            ])
            ->withCount('segments')
            ->when(
                $q !== '',
                function ($query) use ($q) {
                    $query->where(function ($searchQuery) use ($q) {
                        $searchQuery
                            ->where(
                                'start_point',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'destination',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhere(
                                'trucks_id_truck',
                                'like',
                                "%{$q}%"
                            )
                            ->orWhereHas(
                                'segments',
                                function ($segmentQuery) use ($q) {
                                    $segmentQuery->where(function ($nestedQuery) use ($q) {
                                        $nestedQuery
                                            ->where(
                                                'start_point',
                                                'like',
                                                "%{$q}%"
                                            )
                                            ->orWhere(
                                                'destination',
                                                'like',
                                                "%{$q}%"
                                            );
                                    });
                                }
                            );
                    });
                }
            )
            ->when(
                $trucks_id !== '',
                function ($query) use ($trucks_id) {
                    $query->where(
                        'trucks_id_truck',
                        $trucks_id
                    );
                }
            )
            ->when(
                filled($date_from),
                function ($query) use ($date_from) {
                    $query->whereDate(
                        'date_record',
                        '>=',
                        $date_from
                    );
                }
            )
            ->when(
                filled($date_to),
                function ($query) use ($date_to) {
                    $query->whereDate(
                        'date_record',
                        '<=',
                        $date_to
                    );
                }
            )
            ->orderByDesc('date_record')
            ->orderByDesc('id_fuel_record')
            ->paginate(10)
            ->withQueryString();

        $trucks = Truck::query()
            ->orderBy('id_truck')
            ->get();

        return view(
            'fuel_records.index',
            compact(
                'records',
                'trucks',
                'q',
                'trucks_id',
                'date_from',
                'date_to'
            )
        );
    }

    public function create(): View
    {
        $trucks = Truck::available()
            ->with('brand')
            ->orderBy('id_truck')
            ->get();

        $camps = Camp::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('customer')
            ->orderBy('name_camp')
            ->get();

        $dieselPrice = $this->getDieselPrice();

        return view(
            'fuel_records.create',
            compact(
                'trucks',
                'camps',
                'dieselPrice'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateFuelRecord(
            $request
        );

        $truck = Truck::query()
            ->where(
                'id_truck',
                $data['trucks_id_truck']
            )
            ->firstOrFail();

        $calculated = $this->calculateSegments(
            $data,
            $truck
        );

        DB::transaction(function () use (
            $data,
            $truck,
            $calculated
        ) {
            $fuelRecord = FuelRecord::create([
                'date_record' =>
                    $data['date_record'],

                'start_point' =>
                    $data['start_point'],

                'destination' =>
                    $calculated['last_destination'],

                'trucks_id_truck' =>
                    $data['trucks_id_truck'],

                'current_weight' =>
                    $calculated['maximum_load_weight'],

                'max_load' =>
                    (float) $truck->weight_truck,

                'distance' =>
                    $calculated['total_distance'],

                'cost_fuel' =>
                    $data['cost_fuel'],

                'cost_fuel_total' =>
                    $calculated['total_fuel_cost'],

                'total_fuel_liters' =>
                    $calculated['total_fuel_liters'],
            ]);

            $fuelRecord->segments()->createMany(
                $calculated['segments']
            );
        });

        return redirect()
            ->route('fuel_records.index')
            ->with(
                'ok',
                'บันทึกข้อมูลน้ำมันเรียบร้อย'
            );
    }

    public function show(
        FuelRecord $fuel_record
    ): View {
        $fuel_record->load([
            'truck.brand',
            'truck.model',
            'segments',
        ]);

        return view(
            'fuel_records.show',
            compact(
                'fuel_record'
            )
        );
    }

    public function edit(
        FuelRecord $fuel_record
    ): View {
        $fuel_record->load(
            'segments'
        );

        $trucks = Truck::query()
            ->where(function ($query) use ($fuel_record) {
                $query
                    ->where(
                        'status_truck',
                        'active'
                    )
                    ->orWhere(
                        'id_truck',
                        $fuel_record->trucks_id_truck
                    );
            })
            ->with('brand')
            ->orderBy('id_truck')
            ->get();

        $camps = Camp::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('customer')
            ->orderBy('name_camp')
            ->get();

        $dieselPrice =
            $fuel_record->cost_fuel;

        return view(
            'fuel_records.create',
            compact(
                'fuel_record',
                'trucks',
                'camps',
                'dieselPrice'
            )
        );
    }

    public function update(
        Request $request,

        FuelRecord $fuel_record
    ): RedirectResponse {
        $data = $this->validateFuelRecord(
            $request,
            $fuel_record
        );

        $truck = Truck::query()
            ->where(
                'id_truck',
                $data['trucks_id_truck']
            )
            ->firstOrFail();

        $calculated = $this->calculateSegments(
            $data,
            $truck
        );

        DB::transaction(function () use (
            $fuel_record,
            $data,
            $truck,
            $calculated
        ) {
            $fuel_record->update([
                'date_record' =>
                    $data['date_record'],

                'start_point' =>
                    $data['start_point'],

                'destination' =>
                    $calculated['last_destination'],

                'trucks_id_truck' =>
                    $data['trucks_id_truck'],

                'current_weight' =>
                    $calculated['maximum_load_weight'],

                'max_load' =>
                    (float) $truck->weight_truck,

                'distance' =>
                    $calculated['total_distance'],

                'cost_fuel' =>
                    $data['cost_fuel'],

                'cost_fuel_total' =>
                    $calculated['total_fuel_cost'],

                'total_fuel_liters' =>
                    $calculated['total_fuel_liters'],
            ]);

            $fuel_record->segments()->delete();

            $fuel_record->segments()->createMany(
                $calculated['segments']
            );
        });

        return redirect()
            ->route('fuel_records.index')
            ->with(
                'ok',
                'แก้ไขข้อมูลน้ำมันเรียบร้อย'
            );
    }

    public function destroy(
        FuelRecord $fuel_record
    ): RedirectResponse {
        $fuel_record->delete();

        return redirect()
            ->route('fuel_records.index')
            ->with(
                'ok',
                'ลบข้อมูลบันทึกน้ำมันเรียบร้อย'
            );
    }

    private function validateFuelRecord(
        Request $request,

        ?FuelRecord $fuelRecord = null
    ): array {
        $truckRule = Rule::exists(
            'trucks',
            'id_truck'
        )->where(function ($query) use ($fuelRecord) {
            $query->where(function ($nestedQuery) use ($fuelRecord) {
                $nestedQuery->where(
                    'status_truck',
                    'active'
                );

                if ($fuelRecord) {
                    $nestedQuery->orWhere(
                        'id_truck',
                        $fuelRecord->trucks_id_truck
                    );
                }
            });
        });

        return $request->validate([
            'date_record' => [
                'required',
                'date',
            ],

            'start_point' => [
                'required',
                'string',
                'max:255',
            ],

            'trucks_id_truck' => [
                'required',
                $truckRule,
            ],

            'cost_fuel' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'segments' => [
                'required',
                'array',
                'min:1',
            ],

            'segments.*.destination' => [
                'required',
                'string',
                'max:255',
            ],

            'segments.*.load_weight' => [
                'required',
                'numeric',
                'min:0',
            ],

            'segments.*.distance' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ], [
            'date_record.required' =>
                'กรุณาระบุวันที่',

            'start_point.required' =>
                'กรุณาระบุจุดเริ่มต้น',

            'trucks_id_truck.required' =>
                'กรุณาเลือกรถบรรทุก',

            'trucks_id_truck.exists' =>
                'รถคันนี้ไม่พร้อมใช้งาน หรือไม่พบข้อมูลรถ',

            'cost_fuel.required' =>
                'กรุณาตรวจสอบราคาน้ำมัน',

            'cost_fuel.gt' =>
                'ราคาน้ำมันต้องมากกว่า 0 บาท',

            'segments.required' =>
                'กรุณาเพิ่มปลายทางอย่างน้อย 1 จุด',

            'segments.min' =>
                'กรุณาเพิ่มปลายทางอย่างน้อย 1 จุด',

            'segments.*.destination.required' =>
                'กรุณาระบุปลายทางให้ครบทุกช่วง',

            'segments.*.load_weight.required' =>
                'กรุณากรอกน้ำหนักบรรทุกให้ครบทุกช่วง',

            'segments.*.load_weight.min' =>
                'น้ำหนักบรรทุกต้องไม่ติดลบ',

            'segments.*.distance.required' =>
                'กรุณาคำนวณระยะทางให้ครบทุกช่วง',

            'segments.*.distance.gt' =>
                'ระยะทางต้องมากกว่า 0 กิโลเมตร',
        ]);
    }

    private function calculateSegments(
        array $data,

        Truck $truck
    ): array {
        $maximumLoad =
            (float) $truck->weight_truck;

        $emptyFuelRate =
            (float) $truck->fuel_rate;

        $fuelPrice =
            (float) $data['cost_fuel'];

        if ($maximumLoad <= 0) {
            throw ValidationException::withMessages([
                'trucks_id_truck' =>
                    'รถคันนี้ไม่มีข้อมูลน้ำหนักบรรทุกสูงสุด',
            ]);
        }

        if ($emptyFuelRate <= 0) {
            throw ValidationException::withMessages([
                'trucks_id_truck' =>
                    'รถคันนี้ไม่มีข้อมูลอัตราสิ้นเปลือง',
            ]);
        }

        $calculatedSegments = [];

        $totalDistance = 0;

        $totalFuelLiters = 0;

        $totalFuelCost = 0;

        $maximumLoadWeight = 0;

        $currentStartPoint =
            $data['start_point'];

        foreach (
            $data['segments'] as $index => $segment
        ) {
            $loadWeight =
                (float) $segment['load_weight'];

            $distance =
                (float) $segment['distance'];

            $destination =
                trim(
                    (string) $segment['destination']
                );

            if ($loadWeight > $maximumLoad) {
                throw ValidationException::withMessages([
                    "segments.{$index}.load_weight" =>
                        'น้ำหนักบรรทุกช่วงที่ ' .
                        ($index + 1) .
                        " ต้องไม่เกิน {$maximumLoad} ตัน",
                ]);
            }

            $loadRatio =
                $loadWeight / $maximumLoad;

            $fuelRate =
                $emptyFuelRate *
                (
                    1 -
                    (
                        $loadRatio *
                        self::FULL_LOAD_EFFICIENCY_LOSS
                    )
                );

            if ($fuelRate <= 0) {
                throw ValidationException::withMessages([
                    "segments.{$index}.load_weight" =>
                        'ไม่สามารถคำนวณอัตราสิ้นเปลืองของช่วงนี้ได้',
                ]);
            }

            $fuelLiters =
                $distance / $fuelRate;

            $fuelCost =
                $fuelLiters * $fuelPrice;

            $roundedDistance =
                round(
                    $distance,
                    2
                );

            $roundedFuelLiters =
                round(
                    $fuelLiters,
                    2
                );

            $roundedFuelCost =
                round(
                    $fuelCost,
                    2
                );

            $calculatedSegments[] = [
                'sequence' =>
                    $index + 1,

                'start_point' =>
                    $currentStartPoint,

                'destination' =>
                    $destination,

                'load_weight' =>
                    round(
                        $loadWeight,
                        2
                    ),

                'distance' =>
                    $roundedDistance,

                'fuel_rate' =>
                    round(
                        $fuelRate,
                        2
                    ),

                'fuel_liters' =>
                    $roundedFuelLiters,

                'fuel_cost' =>
                    $roundedFuelCost,
            ];

            $totalDistance +=
                $roundedDistance;

            $totalFuelLiters +=
                $roundedFuelLiters;

            $totalFuelCost +=
                $roundedFuelCost;

            $maximumLoadWeight =
                max(
                    $maximumLoadWeight,
                    $loadWeight
                );

            $currentStartPoint =
                $destination;
        }

        return [
            'segments' =>
                $calculatedSegments,

            'total_distance' =>
                round(
                    $totalDistance,
                    2
                ),

            'total_fuel_liters' =>
                round(
                    $totalFuelLiters,
                    2
                ),

            'total_fuel_cost' =>
                round(
                    $totalFuelCost,
                    2
                ),

            'maximum_load_weight' =>
                round(
                    $maximumLoadWeight,
                    2
                ),

            'last_destination' =>
                $currentStartPoint,
        ];
    }

    private function getDieselPrice(): string
    {
        try {
            $response = Http::timeout(10)
                ->get(
                    'https://oil-price.bangchak.co.th/apioilprice2/th'
                );

            if (!$response->successful()) {
                return '';
            }

            $data =
                $response->json();

            if (
                empty($data) ||
                !isset($data[0]['OilList'])
            ) {
                return '';
            }

            $oilList =
                json_decode(
                    $data[0]['OilList'],
                    true
                );

            if (!is_array($oilList)) {
                return '';
            }

            $diesel =
                collect($oilList)->firstWhere(
                    'OilName',
                    'ไฮดีเซล S'
                );

            if (!$diesel) {
                return '';
            }

            return (string) (
                $diesel['PriceToday']
                ?? $diesel['PriceTomorrow']
                ?? ''
            );
        } catch (\Throwable $exception) {
            report(
                $exception
            );

            return '';
        }
    }
}