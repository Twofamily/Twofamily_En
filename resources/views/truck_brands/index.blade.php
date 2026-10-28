@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ยี่ห้อรถบรรทุก</h3>
    </div>
@endsection

@section('content')
    @php
        // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
        $canEdit = auth()->user()->canEdit('truck_brands');
    @endphp
    <div class="container py-4">

        <x-search-bar :action="route('truck_brands.index')"
                      placeholder="ค้นหาชื่อยี่ห้อ"
                      :search="$search" :per-page="$perPage" />

        <div class="d-flex justify-content-end mb-3">
            @if ($canEdit)
            <a href="{{ route('truck_brands.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มยี่ห้อรถบรรทุก
            </a>
            @endif
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ยี่ห้อ</th>
                        <th class="text-center">จำนวนรุ่น</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td>{{ $brand->name_brand }}</td>

                            <td class="text-center">{{ $brand->models_count }}</td>

                            <td class="text-center">
                                <x-row-actions :edit="$canEdit ? route('truck_brands.edit', $brand) : null"
                                               :delete="$canEdit ? (route('truck_brands.destroy', $brand)) : null"
                                               :label="$brand->name_brand" :delete-confirm="'ยี่ห้อ ' . $brand->name_brand . ' จะถูกลบออกจากระบบ'"
                                               delete-title="ยืนยันการลบยี่ห้อรถบรรทุก" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination-footer', ['paginator' => $brands])
    </div>
@endsection