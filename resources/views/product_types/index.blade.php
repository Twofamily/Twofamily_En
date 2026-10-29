@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ประเภทสินค้าทั้งหมด</h3>
    </div>
@endsection

@section('content')
    @php
        $canEdit = auth()->user()->canEdit('product_types');
    @endphp
    <div class="container py-3">

        <div class="mb-2">
            <label class="form-label fw-bold">ค้นหา</label>
        </div>

        <x-search-bar :action="route('product_types.index')" placeholder="ชื่อประเภทสินค้า" :search="$search" :per-page="$perPage" />

        <div class="d-flex justify-content-end align-items-center mb-3">
            @if ($canEdit)
            <a href="{{ route('product_types.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มประเภทสินค้า
            </a>
            @endif
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อประเภทสินค้า</th>
                        <th class="text-center" style="width:80px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $t)
                        <tr>
                            <td>{{ $t->name_product_type }}</td>

                            <td class="text-center">
                                <x-row-actions :edit="$canEdit ? route('product_types.edit', $t) : null"
                                               :delete="$canEdit ? (route('product_types.destroy', $t)) : null"
                                               :label="$t->name_product_type" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.pagination-footer', ['paginator' => $types])
    </div>
@endsection
