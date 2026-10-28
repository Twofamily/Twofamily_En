@extends('layout')

@section('namepage')
    <div class="container">
        <h3>พนักงานขับรถทั้งหมด</h3>
    </div>
@endsection

@section('content')
    @php
        // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
        $canEdit = auth()->user()->canEdit('drivers');
    @endphp
    <div class="container py-3">

        <x-search-bar :action="route('drivers.index')"
                      placeholder="ค้นหาชื่อ / นามสกุล / เบอร์โทร / เลขบัตรประชาชน"
                      :search="$search" :per-page="$perPage" />

        <div class="d-flex justify-content-end mb-3">
            @if ($canEdit)
            <a href="{{ route('drivers.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มพนักงานขับรถ
            </a>
            @endif
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ</th>
                        <th>ที่อยู่</th>
                        <th>เบอร์</th>
                        <th>เลขบัตร</th>
                        <th class="action-col text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $d)
                        <tr>
                            <td>{{ $d->fname_driver }} {{ $d->lname_driver }}</td>

                            <td class="small text-muted">
                                {{ $d->address_no ? 'บ้านเลขที่ ' . $d->address_no : '' }}
                                {{ $d->moo ? 'หมู่ ' . $d->moo : '' }}
                                {{ $d->address_detail }}
                                {{ $d->subdistrict ? 'ต.' . $d->subdistrict : '' }}
                                {{ $d->district ? 'อ.' . $d->district : '' }}
                                {{ $d->province ? 'จ.' . $d->province : '' }}
                                {{ $d->zipcode }}
                            </td>

                            <td>{{ $d->phone_driver ?: '-' }}</td>
                            <td>{{ $d->citizenid_driver ?: '-' }}</td>

                            <td class="text-center">
                                <x-row-actions :show="route('drivers.show', $d)"
                                               :edit="$canEdit ? route('drivers.edit', $d) : null"
                                               :delete="$canEdit ? (route('drivers.destroy', $d)) : null"
                                               :label="$d->fname_driver . ' ' . $d->lname_driver" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            @include('partials.pagination-footer', ['paginator' => $drivers])
        </div>
    </div>
@endsection