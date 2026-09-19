@extends('layout')

@section('namepage')
    <div class="container">
        <h3>พนักงานขับรถทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" method="GET" action="{{ route('drivers.index') }}"
                style="max-width: 500px;">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / เบอร์ / เลขบัตร">

                <button class="btn btn-outline-secondary text-nowrap">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>
            </form>

            <a href="{{ route('drivers.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มพนักงานขับรถ
            </a>
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
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('drivers.show', $d) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูข้อมูลพนักงานขับรถ {{ $d->fname_driver }} {{ $d->lname_driver }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- แก้ไขข้อมูล --}}
                                    <a href="{{ route('drivers.edit', $d) }}"
                                        class="btn btn-outline-primary action-button"
                                        title="แก้ไขข้อมูล"
                                        aria-label="แก้ไขข้อมูลพนักงานขับรถ {{ $d->fname_driver }} {{ $d->lname_driver }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('drivers.destroy', $d) }}"
                                        class="delete-form"
                                        data-confirm="ข้อมูล {{ $d->fname_driver }} {{ $d->lname_driver }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบข้อมูลพนักงานขับรถ {{ $d->fname_driver }} {{ $d->lname_driver }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
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
            {{ $drivers->withQueryString()->links() }}
        </div>
    </div>
@endsection