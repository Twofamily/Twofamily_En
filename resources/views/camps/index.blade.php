@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แคมป์งานทั้งหมด</h3>
    </div>
@endsection

@section('content')
    <style>
        .table-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .table thead th {
            background: #f7f7f9;
            white-space: nowrap;
        }

        .action-col {
            width: 190px;
            min-width: 190px;
        }

        .addr-col {
            max-width: 260px;
        }

        /*
         * กลุ่มปุ่มจัดการ
         */
        .action-buttons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            white-space: nowrap;
        }

        /*
         * กำหนดปุ่มให้เป็นสี่เหลี่ยมมุมโค้ง
         */
        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 10px;
            background-color: #fff;
            transition:
                transform 0.15s ease-in-out,
                box-shadow 0.15s ease-in-out,
                background-color 0.15s ease-in-out;
        }

        .action-button i {
            font-size: 18px;
            line-height: 1;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.12);
        }

        .action-button:focus {
            box-shadow:
                0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        /*
         * ปุ่มดูข้อมูล
         */
        .action-view {
            color: #0dcaf0;
            border-color: #0dcaf0;
        }

        .action-view:hover {
            color: #fff;
            background-color: #0dcaf0;
            border-color: #0dcaf0;
        }

        /*
         * ทำให้แบบฟอร์มปุ่มลบไม่มีระยะขอบ
         */
        .delete-form {
            display: inline-flex;
            margin: 0;
        }

        @media (max-width: 767.98px) {
            .search-button-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }

            .add-camp-button {
                width: 100%;
            }
        }
    </style>

    <div class="container py-3">
        {{-- ข้อความแจ้งผลการทำงาน --}}
        

        @if (session('info'))
            <div
                class="alert alert-info alert-dismissible fade show shadow-sm"
                role="alert"
            >
                {{ session('info') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="ปิด"
                ></button>
            </div>
        @endif

        @if (session('error'))
            <div
                class="alert alert-danger alert-dismissible fade show shadow-sm"
                role="alert"
            >
                {{ session('error') }}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="ปิด"
                ></button>
            </div>
        @endif

        {{-- ช่องค้นหาและตัวกรอง --}}
        <div class="row g-2 align-items-center mb-3">
            <div class="col-lg-10">
                <form
                    method="GET"
                    action="{{ route('camps.index') }}"
                    class="row g-2"
                >
                    {{-- ค้นหาด้วยข้อความ --}}
                    <div class="col-md-5">
                        <input
                            type="search"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            class="form-control"
                            placeholder="ค้นหา: ชื่อแคมป์ / รหัส / ลูกค้า"
                            autocomplete="off"
                        >
                    </div>

                    {{-- กรองตามลูกค้า --}}
                    <div class="col-md-3">
                        <select
                            name="customer"
                            class="form-select"
                            aria-label="กรองตามลูกค้า"
                        >
                            <option value="">ลูกค้าทั้งหมด</option>

                            @foreach ($customers as $customer)
                                <option
                                    value="{{ $customer->id_customer }}"
                                    @selected(
                                        ($customerId ?? request('customer')) ==
                                        $customer->id_customer
                                    )
                                >
                                    {{ $customer->name_customer }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- กรองตามสถานะ --}}
                    <div class="col-md-2">
                        <select
                            name="status"
                            class="form-select"
                            aria-label="กรองตามสถานะ"
                        >
                            <option value="">ทุกสถานะ</option>

                            @foreach (\App\Models\Camp::STATUS_LABELS as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        ($status ?? request('status')) ===
                                        $value
                                    )
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ปุ่มค้นหา --}}
                    <div class="col-md-2">
                        <button
                            class="btn btn-outline-secondary w-100"
                            type="submit"
                        >
                            <i class="bi bi-search me-1"></i>
                            ค้นหา
                        </button>
                    </div>

                    {{-- ปุ่มล้างการค้นหา --}}
                    @if (
                        filled($q ?? request('q')) ||
                        filled($customerId ?? request('customer')) ||
                        filled($status ?? request('status'))
                    )
                        <div class="col-12">
                            <a
                                href="{{ route('camps.index') }}"
                                class="btn btn-link btn-sm text-decoration-none px-0"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                ล้างการค้นหาและตัวกรอง
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            {{-- ปุ่มเพิ่มแคมป์ --}}
            <div class="col-lg-2 text-lg-end">
                <a
                    href="{{ route('camps.create') }}"
                    class="btn btn-dark add-camp-button"
                >
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มแคมป์
                </a>
            </div>
        </div>

        {{-- ตารางข้อมูลแคมป์ --}}
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อแคมป์</th>
                            <th>ลูกค้า</th>
                            <th>ที่ตั้ง</th>
                            <th>ผู้ติดต่อ</th>
                            <th class="text-center">รถ</th>
                            <th>สถานะ</th>
                            <th class="action-col text-center">
                                จัดการ
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($camps as $camp)
                            <tr>
                                {{-- รหัสแคมป์ --}}
                                <td class="fw-semibold">
                                    {{ $camp->code_camp }}
                                </td>

                                {{-- ชื่อแคมป์ --}}
                                <td>
                                    {{ $camp->name_camp }}
                                </td>

                                {{-- ชื่อลูกค้า --}}
                                <td>
                                    {{ $camp->customer?->name_customer ?? '-' }}
                                </td>

                                {{-- ที่ตั้ง --}}
                                <td class="small addr-col">
                                    {{ $camp->full_address ?: '-' }}
                                </td>

                                {{-- ผู้ติดต่อ --}}
                                <td class="small">
                                    {{ $camp->contact_name ?: '-' }}

                                    @if ($camp->contact_phone)
                                        <div class="text-muted mt-1">
                                            <i class="bi bi-telephone me-1"></i>
                                            {{ $camp->contact_phone }}
                                        </div>
                                    @endif
                                </td>

                                {{-- จำนวนรถ --}}
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ $camp->trucks_count ?? 0 }} คัน
                                    </span>
                                </td>

                                {{-- สถานะ --}}
                                <td>
                                    <span
                                        class="badge bg-{{ $camp->status_camp === 'active'
                                            ? 'success'
                                            : 'secondary' }}"
                                    >
                                        {{ $camp->status_label }}
                                    </span>
                                </td>

                                {{-- ปุ่มจัดการ --}}
                                <td class="text-center">
                                    <div class="action-buttons">
                                        {{-- ดูข้อมูล --}}
                                        <a
                                            href="{{ route(
                                                'camps.show',
                                                $camp->id_camp
                                            ) }}"
                                            class="btn action-button action-view"
                                            title="ดูข้อมูล"
                                            aria-label="ดูข้อมูลแคมป์ {{ $camp->name_camp }}"
                                        >
                                            <i
                                                class="bi bi-eye"
                                                aria-hidden="true"
                                            ></i>
                                        </a>

                                        {{-- แก้ไขข้อมูล --}}
                                        <a
                                            href="{{ route(
                                                'camps.edit',
                                                $camp->id_camp
                                            ) }}"
                                            class="btn btn-outline-primary action-button"
                                            title="แก้ไขข้อมูล"
                                            aria-label="แก้ไขข้อมูลแคมป์ {{ $camp->name_camp }}"
                                        >
                                            <i
                                                class="bi bi-pencil-square"
                                                aria-hidden="true"
                                            ></i>
                                        </a>

                                        {{-- ลบข้อมูล --}}
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'camps.destroy',
                                                $camp
                                            ) }}"
                                            class="delete-form"
                                            data-confirm="ข้อมูล {{ $camp->code_camp }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบข้อมูลแคมป์ {{ $camp->name_camp }}"
                                            >
                                                <i
                                                    class="bi bi-trash"
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="8"
                                    class="text-center text-muted py-5"
                                >
                                    <i
                                        class="bi bi-inbox fs-2 d-block mb-2"
                                    ></i>

                                    ไม่พบข้อมูลแคมป์
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($camps->hasPages())
                <div class="p-3 border-top">
                    {{ $camps->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection