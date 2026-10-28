@extends('layout')

{{--
    หน้ารถบรรทุก — 3 แท็บ
      ?tab=trucks (ค่าเริ่มต้น) : รถบรรทุกในบริษัท
      ?tab=models              : รุ่นรถ
      ?tab=brands              : ยี่ห้อ

    ปุ่มเพิ่มของแต่ละแท็บเปิดเป็น modal ในหน้านี้ ไม่ย้ายหน้า
    วาดเฉพาะ modal ของแท็บที่เปิดอยู่ — ฟอร์มรถกับฟอร์มรุ่นมีช่องชื่อซ้ำกัน (fuel_rate, cubic_capacity)
    ถ้าวาดพร้อมกัน old() จะไปเติมค่าผิดฟอร์ม

    กรอกผิดแล้ว server ส่งกลับมา → modal เดิมเด้งขึ้นพร้อมค่าที่กรอกไว้ (ดูจาก _modal ที่ฟอร์มส่งไป)
--}}

@php
    $user = auth()->user();

    // modal ของแต่ละแท็บ: [id, ค่า _modal, สิทธิ์แก้ไข, ข้อความปุ่ม]
    $modalOf = [
        'trucks' => ['id' => 'truckCreateModal', 'key' => 'truck', 'module' => 'trucks',       'button' => 'เพิ่มรถบรรทุก'],
        'models' => ['id' => 'modelCreateModal', 'key' => 'model', 'module' => 'truck_models', 'button' => 'เพิ่มรุ่นรถ'],
        'brands' => ['id' => 'brandCreateModal', 'key' => 'brand', 'module' => 'truck_brands', 'button' => 'เพิ่มยี่ห้อ'],
    ];
    $modal   = $modalOf[$tab];
    $canEdit = (bool) $user?->canEdit($modal['module']);

    // เปิด modal ทันทีตอนโหลดหน้า: กรอกผิดแล้วเด้งกลับ หรือมาจากลิงก์ ?open=
    $openModal = $canEdit && (old('_modal') ?? request('open')) === $modal['key'] ? $modal['id'] : null;
@endphp

@section('namepage')
    <div class="container">
        <h3 class="mb-0">รถบรรทุก</h3>
    </div>
@endsection

@section('content')
    <style>
        .truck-tabs .nav-link { color: #525252; font-weight: 500; }
        .truck-tabs .nav-link.active { color: #1a1a1a; font-weight: 600; }

        .truck-tabs .count-badge {
            background: rgba(0, 0, 0, .06);
            color: #404040;
            font-weight: 600;
            margin-left: .35rem;
        }

        .truck-tabs .nav-link.active .count-badge { background: #1a1a1a; color: #fff; }

        .table-card {
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
            border-radius: 14px;
            overflow: hidden;
        }

        .table-card .table thead th { background: #f7f7f9; }
    </style>

    <div class="container py-3">

        @if (session('info'))
            <div class="alert alert-info shadow-sm">{{ session('info') }}</div>
        @endif

        {{-- ===== แท็บ ===== --}}
        <ul class="nav nav-tabs truck-tabs mb-3">
            @foreach ($tabs as $key => $t)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                       href="{{ $key === 'trucks' ? route('trucks.index') : route('trucks.index', ['tab' => $key]) }}"
                       @if ($tab === $key) aria-current="page" @endif>
                        <i class="bi {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
                        <span class="badge rounded-pill count-badge">{{ number_format($counts[$key]) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- ===== แถบค้นหา (แต่ละแท็บมีตัวกรองของตัวเอง) ===== --}}
        @if ($tab === 'trucks')
            <x-search-bar :action="route('trucks.index')"
                          placeholder="ค้นหาทะเบียน / จังหวัด / ยี่ห้อ / รุ่น / ปี / สถานะ เช่น ซ่อมบำรุง"
                          :search="$search" :per-page="$perPage"
                          :statuses="\App\Models\Truck::STATUS_LABELS" :status="$status"
                          :ranges="$ranges" />
        @elseif ($tab === 'models')
            <x-search-bar :action="route('trucks.index', ['tab' => 'models'])"
                          placeholder="ค้นหาชื่อรุ่น / ยี่ห้อ / ประเภท / เชื้อเพลิง / ปีรุ่น"
                          :search="$search" :per-page="$perPage"
                          :statuses="$statusLabels" :status="$status"
                          :ranges="$ranges">
                {{-- GET form ไม่ส่ง query ที่อยู่ใน action ต้องแนบแท็บเป็น hidden เอง --}}
                <input type="hidden" name="tab" value="models">
            </x-search-bar>
        @else
            <x-search-bar :action="route('trucks.index', ['tab' => 'brands'])"
                          placeholder="ค้นหาชื่อยี่ห้อ"
                          :search="$search" :per-page="$perPage">
                <input type="hidden" name="tab" value="brands">
            </x-search-bar>
        @endif

        @if ($canEdit)
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-dark text-nowrap"
                        data-bs-toggle="modal" data-bs-target="#{{ $modal['id'] }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ $modal['button'] }}
                </button>
            </div>
        @endif

        {{-- ================= แท็บ: รถบรรทุก ================= --}}
        @if ($tab === 'trucks')
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>เลขทะเบียน</th>
                                <th>ยี่ห้อ</th>
                                <th>รุ่น</th>
                                <th>ปีที่ซื้อ</th>
                                <th>จังหวัด</th>
                                <th>สถานะ</th>
                                <th class="action-col text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trucks as $t)
                                <tr>
                                    <td class="fw-semibold">{{ $t->id_truck }}</td>
                                    <td>{{ $t->brand->name_brand ?? '-' }}</td>
                                    <td>{{ $t->model->name_model ?? '-' }}</td>
                                    <td>{{ $t->year_truck }}</td>
                                    <td>{{ $t->province_truck }}</td>
                                    <td>
                                        <span class="badge bg-{{ $t->status_color }}">{{ $t->status_label }}</span>
                                    </td>
                                    <td class="text-center">
                                        <x-row-actions :show="route('trucks.show', $t->id_truck)"
                                                       :edit="$canEdit ? route('trucks.edit', $t->id_truck) : null"
                                                       :delete="$canEdit ? route('trucks.destroy', $t->id_truck) : null"
                                                       :label="$t->id_truck"
                                                       :delete-confirm="'รถบรรทุกทะเบียน ' . $t->id_truck . ' จะถูกลบออกจากระบบ'"
                                                       delete-title="ยืนยันการลบรถบรรทุก" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ไม่พบข้อมูล
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('partials.pagination-footer', ['paginator' => $trucks])

        {{-- ================= แท็บ: รุ่นรถ ================= --}}
        @elseif ($tab === 'models')
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ยี่ห้อ</th>
                                <th>รุ่น</th>
                                <th class="text-center">ปี</th>
                                <th class="text-center">ล้อ</th>
                                <th class="text-end">คิว</th>
                                <th class="text-end">กม./ลิตร</th>
                                <th class="text-center">รถที่ใช้</th>
                                <th class="text-center" style="width:80px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($models as $m)
                                <tr class="{{ $m->is_active ? '' : 'text-muted' }}">
                                    <td>{{ $m->brand->name_brand ?? '-' }}</td>
                                    <td>
                                        {{ $m->name_model }}
                                        @unless ($m->is_active)
                                            <span class="badge text-bg-light border ms-1">ปิดใช้งาน</span>
                                        @endunless
                                    </td>
                                    <td class="text-center">{{ $m->model_year ?? '-' }}</td>
                                    <td class="text-center">{{ $m->wheels ?? '-' }}</td>
                                    <td class="text-end">{{ $m->cubic_capacity ?? '-' }}</td>
                                    <td class="text-end">{{ $m->fuel_rate ?? '-' }}</td>
                                    <td class="text-center">{{ number_format($m->trucks_count) }} คัน</td>

                                    <td class="text-center">
                                        {{-- ดู = หน้าต่างสเปคเต็ม (รุ่นรถไม่มีหน้า show แยก)
                                             มีรถใช้อยู่ ลบไม่ได้ (controller กันไว้อีกชั้น) --}}
                                        <x-row-actions :delete="! $canEdit ? null : ($m->trucks_count > 0 ? null : route('truck_models.destroy', $m))"
                                                       :delete-disabled="$canEdit && $m->trucks_count > 0 ? 'มีรถใช้รุ่นนี้อยู่' : null"
                                                       :label="$m->name_model"
                                                       :delete-confirm="'รุ่น ' . $m->name_model . ' จะถูกลบออกจากระบบ'"
                                                       delete-title="ยืนยันการลบรุ่นรถบรรทุก">
                                            @include('truck_models._view-item', ['m' => $m, 'canEdit' => $canEdit])
                                            @if ($canEdit)
                                                <x-row-actions.link :href="route('truck_models.edit', $m)" icon="bi-pencil-square">แก้ไข</x-row-actions.link>
                                            @endif
                                        </x-row-actions>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ยังไม่มีข้อมูล
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                @include('partials.pagination-footer', ['paginator' => $models])
            </div>

            @include('truck_models._detail-modal')

        {{-- ================= แท็บ: ยี่ห้อ ================= --}}
        @else
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ยี่ห้อ</th>
                                <th class="text-center">จำนวนรุ่น</th>
                                @if ($canEdit)
                                    <th class="text-center" style="width:80px;">จัดการ</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($brands as $brand)
                                <tr>
                                    <td class="fw-semibold">{{ $brand->name_brand }}</td>
                                    <td class="text-center">
                                        @if ($brand->models_count > 0)
                                            <a href="{{ route('trucks.index', ['tab' => 'models', 'search' => $brand->name_brand]) }}"
                                               class="text-decoration-none" title="ดูรุ่นของยี่ห้อนี้">
                                                {{ number_format($brand->models_count) }} รุ่น
                                            </a>
                                        @else
                                            <span class="text-muted">0 รุ่น</span>
                                        @endif
                                    </td>

                                    @if ($canEdit)
                                        <td class="text-center">
                                            {{-- มีรุ่นผูกอยู่ ลบไม่ได้ (controller กันไว้อีกชั้น) --}}
                                            <x-row-actions :edit="route('truck_brands.edit', $brand)"
                                                           :delete="$brand->models_count > 0 ? null : route('truck_brands.destroy', $brand)"
                                                           :delete-disabled="$brand->models_count > 0 ? 'มีรุ่นรถผูกกับยี่ห้อนี้อยู่' : null"
                                                           :label="$brand->name_brand"
                                                           :delete-confirm="'ยี่ห้อ ' . $brand->name_brand . ' จะถูกลบออกจากระบบ'"
                                                           delete-title="ยืนยันการลบยี่ห้อรถบรรทุก" />
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canEdit ? 3 : 2 }}" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ยังไม่มีข้อมูล
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('partials.pagination-footer', ['paginator' => $brands])
        @endif
    </div>

    {{-- ================= Modal ของแท็บที่เปิดอยู่ ================= --}}
    @if ($canEdit)
        @php
            [$action, $title, $size, $confirm] = match ($tab) {
                'trucks' => [route('trucks.store'),       'เพิ่มรถบรรทุก', 'modal-xl', 'ต้องการบันทึกรถบรรทุกคันนี้หรือไม่'],
                'models' => [route('truck_models.store'), 'เพิ่มรุ่นรถ',    'modal-xl', 'ต้องการบันทึกรุ่นรถบรรทุกนี้หรือไม่'],
                default  => [route('truck_brands.store'), 'เพิ่มยี่ห้อ',     '',         'ต้องการบันทึกยี่ห้อรถบรรทุกนี้หรือไม่'],
            };
            // old() ของฟอร์มนี้ใช้ได้เฉพาะตอนที่เพิ่งส่งฟอร์มนี้แล้วเด้งกลับ
            $ownErrors = old('_modal') === $modal['key'] && $errors->any();
        @endphp

        <div class="modal fade" id="{{ $modal['id'] }}" tabindex="-1" aria-labelledby="{{ $modal['id'] }}Title" aria-hidden="true">
            <div class="modal-dialog {{ $size }} modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ $action }}" autocomplete="off"
                      @if ($tab === 'trucks') enctype="multipart/form-data" @endif
                      class="modal-content border-0 shadow"
                      data-confirm="{{ $confirm }}" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf
                    <input type="hidden" name="_modal" value="{{ $modal['key'] }}">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="{{ $modal['id'] }}Title">{{ $title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        @if ($ownErrors)
                            <div class="alert alert-danger py-2 small">
                                กรุณาตรวจสอบข้อมูลที่มีกรอบสีแดง
                                @if ($tab === 'trucks') · ถ้าแนบรูปไว้ ต้องเลือกรูปใหม่อีกครั้ง @endif
                            </div>
                        @endif

                        @if ($tab === 'trucks')
                            @if ($brandOptions->isEmpty())
                                <div class="alert alert-warning py-2 small">
                                    ยังไม่มียี่ห้อและรุ่นรถ — เพิ่มในแท็บยี่ห้อและรุ่นรถก่อน
                                </div>
                            @endif
                            @include('trucks._form', [
                                'truck'     => new \App\Models\Truck(),
                                'mode'      => 'create',
                                'brands'    => $brandOptions,
                                'provinces' => config('provinces'),
                            ])
                        @elseif ($tab === 'models')
                            @if ($brandOptions->isEmpty())
                                <div class="alert alert-warning py-2 small">
                                    ยังไม่มียี่ห้อรถ — เพิ่มในแท็บยี่ห้อก่อน
                                </div>
                            @endif
                            @include('truck_models._form', [
                                'model'  => new \App\Models\TruckModel(),
                                'brands' => $brandOptions,
                            ])
                        @else
                            @include('truck_brands._form', ['brand' => new \App\Models\TruckBrand()])
                            <div class="form-text mt-n2">ระบบเปลี่ยนเป็นตัวพิมพ์ใหญ่ให้อัตโนมัติ</div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalEl = document.getElementById(@json($modal['id']));
                if (!modalEl) return;

                // เปิดแล้วโฟกัสช่องพิมพ์ช่องแรก
                modalEl.addEventListener('shown.bs.modal', function () {
                    const first = modalEl.querySelector('input[type="text"]:not([readonly])');
                    if (first) first.focus();
                });

                // เปิดให้อัตโนมัติ (กรอกผิดแล้วเด้งกลับ / ลิงก์ ?open=)
                if (@json($openModal)) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();

                    // ลบ ?open= ออกจาก URL กดรีเฟรชแล้วจะได้ไม่เด้งซ้ำ
                    const url = new URL(window.location.href);
                    if (url.searchParams.has('open')) {
                        url.searchParams.delete('open');
                        history.replaceState(null, '', url);
                    }
                }
            });
        </script>
    @endif
@endsection
