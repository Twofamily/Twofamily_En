@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ใบส่งของ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('delivery-notes.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                สร้างใบส่งของ
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>แคมป์</th>
                        <th>อ้างอิงใบเสนอราคา</th>
                        <th>วันที่ส่ง</th>
                        <th class="text-center">สถานะใบแจ้งหนี้</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveryNotes as $deliveryNote)
                        <tr>
                            <td><strong>{{ $deliveryNote->code_delivery }}</strong></td>

                            <td>{{ $deliveryNote->customer->name_customer ?? '-' }}</td>

                            <td>
                                @if ($deliveryNote->camp)
                                    <a href="{{ route('camps.show', $deliveryNote->camp->id_camp) }}"
                                       class="text-decoration-none">
                                        {{ $deliveryNote->camp->code_camp }}
                                    </a>
                                    <div class="small text-muted">{{ $deliveryNote->camp->name_camp }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                @if ($deliveryNote->id_quotation)
                                    {{ $deliveryNote->quotation?->code_quot ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>{{ $deliveryNote->delivery_date->format('d/m/Y') }}</td>

                            <td class="text-center">
                                @if ($deliveryNote->invoice)
                                    <span class="badge bg-success">ออกใบแจ้งหนี้แล้ว</span>
                                @else
                                    <span class="badge bg-secondary">รอออกใบแจ้งหนี้</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- ดูข้อมูล --}}
                                    <a href="{{ route('delivery-notes.show', $deliveryNote) }}"
                                        class="btn action-button action-view"
                                        title="ดูข้อมูล"
                                        aria-label="ดูใบส่งของ {{ $deliveryNote->code_delivery }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    {{-- ลบข้อมูล (เฉพาะที่ยังไม่ออกใบแจ้งหนี้) --}}
                                    @if (!$deliveryNote->invoice)
                                        <form method="POST"
                                            action="{{ route('delivery-notes.destroy', $deliveryNote) }}"
                                            class="delete-form"
                                            data-confirm="ใบส่งของ {{ $deliveryNote->code_delivery }} จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบข้อมูล"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบข้อมูล">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบข้อมูล"
                                                aria-label="ลบใบส่งของ {{ $deliveryNote->code_delivery }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ยังไม่มีใบส่งของ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $deliveryNotes->withQueryString()->links() }}</div>
    </div>
@endsection