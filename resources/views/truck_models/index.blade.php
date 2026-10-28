@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รุ่นรถบรรทุก</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        <x-search-bar :action="route('truck_models.index')"
                      placeholder="ค้นหาชื่อรุ่น / ยี่ห้อ / ประเภท / เชื้อเพลิง / ปีรุ่น"
                      :search="$search" :per-page="$perPage"
                      :statuses="$statusLabels" :status="$status"
                      :ranges="$ranges" />

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('truck_models.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มรุ่นรถบรรทุก
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ยี่ห้อ</th>
                        <th>รุ่น</th>
                        <th class="text-center">ปี</th>
                        <th class="text-center">ล้อ</th>
                        <th class="text-end">คิว</th>
                        <th class="text-end">กม./ลิตร</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($models as $m)
                        <tr class="{{ $m->is_active ? '' : 'text-muted' }}">
                            <td>{{ $m->brand->name_brand ?? '-' }}</td>
                            <td>{{ $m->name_model }}</td>
                            <td class="text-center">{{ $m->model_year ?? '-' }}</td>
                            <td class="text-center">{{ $m->wheels ?? '-' }}</td>
                            <td class="text-end">{{ $m->cubic_capacity ?? '-' }}</td>
                            <td class="text-end">{{ $m->fuel_rate ?? '-' }}</td>

                            <td class="text-center">
                                <x-row-actions :delete="route('truck_models.destroy', $m)"
                                               :label="$m->name_model" :delete-confirm="'รุ่น ' . $m->name_model . ' จะถูกลบออกจากระบบ'"
                                               delete-title="ยืนยันการลบรุ่นรถบรรทุก">
                                    @include('truck_models._view-item', ['m' => $m, 'canEdit' => true])
                                    <x-row-actions.link :href="route('truck_models.edit', $m)" icon="bi-pencil-square">แก้ไข</x-row-actions.link>
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            @include('partials.pagination-footer', ['paginator' => $models])
            @include('truck_models._detail-modal')
        </div>
    </div>
@endsection