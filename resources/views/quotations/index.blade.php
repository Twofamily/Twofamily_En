@extends('layout')

@section('namepage')
    <div class="container">
        <h3>รายการใบเสนอราคา</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        {{-- ==================== แถบค้นหา / กรอง / จำนวนต่อหน้า ==================== --}}
        {{--
            ใช้ form เดียวครอบทั้งสามอย่าง เพื่อให้ค่าทุกตัวถูกส่งไปพร้อมกัน
            ถ้าแยกเป็นคนละ form ค่าที่อยู่อีก form จะหายทุกครั้งที่กดค้นหา
        --}}
        <form method="GET" action="{{ route('quotations.index') }}" class="mb-3">
            <div class="row g-2 align-items-center">

                {{-- ช่องค้นหา --}}
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="ค้นหาเลขที่เอกสาร หรือชื่อลูกค้า"
                            value="{{ $search }}">
                    </div>
                </div>

                {{-- กรองสถานะ --}}
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">ทุกสถานะ</option>
                        <option value="draft"    @selected($status === 'draft')>ร่าง</option>
                        <option value="approved" @selected($status === 'approved')>อนุมัติแล้ว</option>
                        <option value="rejected" @selected($status === 'rejected')>ยกเลิก</option>
                    </select>
                </div>

                {{-- ปุ่มค้นหา / ล้าง --}}
                <div class="col-6 col-md-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> ค้นหา
                    </button>

                    @if ($search !== '' || $status !== null)
                        <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>

                {{-- จำนวนรายการต่อหน้า --}}
                <div class="col-12 col-md-auto ms-md-auto">
                    <div class="input-group">
                        <span class="input-group-text bg-white">แสดง</span>

                        {{--
                            เปลี่ยนแล้ว submit ทันที ไม่ต้องกดค้นหาซ้ำ
                            ต้องล้าง page ด้วย ไม่งั้นเลือก 100 แล้วยังค้างอยู่หน้า 5 ซึ่งไม่มีข้อมูล
                        --}}
                        <select name="per_page"
                            class="form-select"
                            onchange="this.form.page.value = 1; this.form.submit();">
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>

                        <span class="input-group-text bg-white">รายการ</span>
                    </div>
                </div>
            </div>

            {{-- รีเซ็ตกลับหน้า 1 ทุกครั้งที่ค้นหาใหม่ --}}
            <input type="hidden" name="page" value="1">
        </form>

        {{-- ==================== ปุ่มสร้าง ==================== --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('quotations.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบเสนอราคา
            </a>
        </div>

        {{-- ==================== ตาราง ==================== --}}
        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">ลำดับ</th>
                        <th>เลขที่ใบเสนอราคา</th>
                        <th>ลูกค้า</th>
                        <th>วันที่ออก</th>
                        <th class="text-end">ยอดสุทธิ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:240px;">จัดการ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotations as $q)
                        <tr>
                            {{--
                                ลำดับที่ต่อเนื่องข้ามหน้า
                                firstItem() คือลำดับของแถวแรกในหน้านี้ เช่น หน้า 2 ที่ 10 ต่อหน้า จะได้ 11
                            --}}
                            <td class="text-center text-muted">
                                {{ $quotations->firstItem() + $loop->index }}
                            </td>

                            <td><strong>{{ $q->code_quot }}</strong></td>

                            <td>{{ $q->customer->name_customer ?? '-' }}</td>

                            <td>{{ \Carbon\Carbon::parse($q->date_quot)->format('d/m/Y') }}</td>

                            <td class="text-end">{{ number_format($q->total_amount, 2) }}</td>

                            <td class="text-center">
                                @if ($q->status == 'draft')
                                    <span class="badge bg-secondary">ร่าง</span>
                                @elseif($q->status == 'approved')
                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                @elseif($q->status == 'rejected')
                                    <span class="badge bg-danger">ยกเลิก</span>
                                @else
                                    <span class="badge bg-light text-dark">ไม่ทราบสถานะ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('quotations.show', $q) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบเสนอราคา {{ $q->code_quot }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- อนุมัติ (เฉพาะสถานะร่าง) --}}
                                    @if ($q->status === 'draft')
                                        <form method="POST"
                                            action="{{ route('quotations.approve', $q) }}"
                                            class="delete-form"
                                            data-confirm="ใบเสนอราคา {{ $q->code_quot }} จะถูกอนุมัติ และสามารถนำไปเปิดแคมป์ได้"
                                            data-confirm-title="ยืนยันการอนุมัติ"
                                            data-confirm-variant="success"
                                            data-confirm-ok="อนุมัติ">
                                            @csrf
                                            @method('PATCH')

                                            <button class="btn btn-outline-success action-button"
                                                type="submit"
                                                title="อนุมัติ"
                                                aria-label="อนุมัติใบเสนอราคา {{ $q->code_quot }}">
                                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- ยกเลิก (สถานะร่างหรืออนุมัติแล้ว) --}}
                                    @if (in_array($q->status, ['draft', 'approved']))
                                        <form method="POST"
                                            action="{{ route('quotations.cancel', $q) }}"
                                            class="delete-form"
                                            data-confirm="ใบเสนอราคา {{ $q->code_quot }} จะถูกยกเลิก"
                                            data-confirm-title="ยืนยันการยกเลิก"
                                            data-confirm-variant="warning"
                                            data-confirm-ok="ยกเลิกใบเสนอราคา">
                                            @csrf
                                            @method('PATCH')

                                            <button class="btn btn-outline-warning action-button"
                                                type="submit"
                                                title="ยกเลิกใบเสนอราคา"
                                                aria-label="ยกเลิกใบเสนอราคา {{ $q->code_quot }}">
                                                <i class="bi bi-slash-circle" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- ลบข้อมูล --}}
                                    <form method="POST"
                                        action="{{ route('quotations.destroy', $q) }}"
                                        class="delete-form"
                                        data-confirm="ใบเสนอราคา {{ $q->code_quot }} จะถูกลบออกจากระบบ"
                                        data-confirm-title="ยืนยันการลบข้อมูล"
                                        data-confirm-variant="danger"
                                        data-confirm-ok="ลบข้อมูล">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-outline-danger action-button"
                                            type="submit"
                                            title="ลบข้อมูล"
                                            aria-label="ลบใบเสนอราคา {{ $q->code_quot }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                                {{-- ข้อความต่างกันระหว่าง "ยังไม่มีข้อมูล" กับ "ค้นหาไม่เจอ" --}}
                                @if ($search !== '' || $status !== null)
                                    ไม่พบใบเสนอราคาที่ตรงกับเงื่อนไขที่ค้นหา
                                    <div class="mt-2">
                                        <a href="{{ route('quotations.index') }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            ล้างเงื่อนไขการค้นหา
                                        </a>
                                    </div>
                                @else
                                    ยังไม่มีใบเสนอราคา
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ==================== สรุปจำนวน + ปุ่มเปลี่ยนหน้า ==================== --}}
        @if ($quotations->total() > 0)
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">

                <div class="text-muted small">
                    แสดง {{ number_format($quotations->firstItem()) }}–{{ number_format($quotations->lastItem()) }}
                    จากทั้งหมด {{ number_format($quotations->total()) }} รายการ
                    @if ($quotations->lastPage() > 1)
                        (หน้า {{ $quotations->currentPage() }} จาก {{ $quotations->lastPage() }})
                    @endif
                </div>

                {{-- withQueryString() ทำไว้ที่ Controller แล้ว ตรงนี้เรียก links() ได้เลย --}}
                <div>
                    {{ $quotations->links() }}
                </div>
            </div>
        @endif

    </div>
@endsection