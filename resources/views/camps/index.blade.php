@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แคมป์งานทั้งหมด</h3>
    </div>
@endsection

@section('content')
    @php
        $canEdit = auth()->user()->canEdit('camps');
    @endphp
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
            width: 80px;
            min-width: 80px;
        }

        .addr-col {
            max-width: 260px;
        }

        .action-buttons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            white-space: nowrap;
        }

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

        .action-view {
            color: #0dcaf0;
            border-color: #0dcaf0;
        }

        .action-view:hover {
            color: #fff;
            background-color: #0dcaf0;
            border-color: #0dcaf0;
        }

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
        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                {{ session('info') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                {{ session('error') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
            </div>
        @endif

        <x-search-bar :action="route('camps.index')" placeholder="รหัสแคมป์ / ชื่อแคมป์ / ลูกค้า / ผู้ติดต่อ" :search="$search"
            :per-page="$perPage" :statuses="\App\Models\Camp::STATUS_LABELS" :status="$status">
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small text-muted mb-1">จังหวัดที่ตั้ง</label>
                <select name="province" class="form-select" onchange="this.form.submit()">
                    <option value="">— ทุกจังหวัด —</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p }}" @selected($province === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </x-search-bar>

        <div class="d-flex justify-content-end mb-3">
            @if ($canEdit)
                <a href="{{ route('camps.create') }}" class="btn btn-dark add-camp-button">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มแคมป์
                </a>
            @endif
        </div>

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
                                <td class="fw-semibold">
                                    {{ $camp->code_camp }}
                                </td>
                                <td>
                                    {{ $camp->name_camp }}
                                </td>
                                <td>
                                    {{ $camp->customer?->name_customer ?? '-' }}
                                </td>
                                <td class="small addr-col">
                                    {{ $camp->full_address ?: '-' }}

                                    @if ($camp->latitude && $camp->longitude)
                                        <div class="mt-1">
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $camp->latitude }},{{ $camp->longitude }}"
                                                target="_blank" rel="noopener" class="text-decoration-none">
                                                <i class="bi bi-geo-alt-fill text-danger"></i> ดูบนแผนที่
                                            </a>
                                        </div>
                                    @else
                                        <div class="mt-1 text-muted">
                                            <i class="bi bi-geo-alt"></i> ยังไม่ปักหมุด
                                        </div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $camp->contact_name ?: '-' }}

                                    @if ($camp->contact_phone)
                                        <div class="text-muted mt-1">
                                            <i class="bi bi-telephone me-1"></i>
                                            {{ $camp->contact_phone }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ $camp->trucks_count ?? 0 }} คัน
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $camp->status_color }}">
                                        {{ $camp->status_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <x-row-actions :show="route('camps.show', $camp->id_camp)" :edit="$canEdit ? route('camps.edit', $camp->id_camp) : null" :delete="$canEdit && !($blocker = $camp->deleteBlocker())
                                        ? route('camps.destroy', $camp)
                                        : null" :delete-disabled="$canEdit ? $blocker ?? null : null"
                                        :label="$camp->code_camp" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    ไม่พบข้อมูลแคมป์
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
        @include('partials.pagination-footer', ['paginator' => $camps])
    </div>
@endsection
