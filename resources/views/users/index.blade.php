@extends('layout')

@section('namepage')
    <div class="container">
        <h3>จัดการผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" style="max-width: 500px;">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / อีเมล">

                <button class="btn btn-outline-secondary text-nowrap">
                    <i class="bi bi-search me-1"></i>
                    ค้นหา
                </button>
            </form>

            <a href="{{ route('users.create') }}" class="btn btn-dark text-nowrap">
                <i class="bi bi-plus-lg me-1"></i>
                เพิ่มผู้ใช้งาน
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>สิทธิ์การใช้งาน</th>
                        <th>สถานะ</th>
                        <th class="text-center" style="width:190px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $u->name }}
                                    @if ($u->id === auth()->id())
                                        <span class="badge bg-secondary ms-1">คุณ</span>
                                    @endif
                                </div>

                                <div class="small text-muted">
                                    <i class="bi bi-envelope me-1"></i>
                                    {{ $u->email }}
                                </div>
                            </td>

                            <td>{{ $u->role_name }}</td>

                            <td>
                                @if ($u->is_active)
                                    <span class="badge bg-success">ใช้งานอยู่</span>
                                @else
                                    <span class="badge bg-danger">ถูกระงับ</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="action-buttons">
                                    {{-- แก้ไขข้อมูล --}}
                                    <a href="{{ route('users.edit', $u) }}"
                                        class="btn btn-outline-primary action-button"
                                        title="แก้ไขข้อมูล"
                                        aria-label="แก้ไขผู้ใช้งาน {{ $u->name }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- ปุ่มระงับและลบ ไม่แสดงในแถวของตนเอง --}}
                                    @if ($u->id !== auth()->id())
                                        {{-- ระงับ / เปิดใช้งาน --}}
                                        <form method="POST"
                                            action="{{ route('users.toggleStatus', $u) }}"
                                            class="delete-form"
                                            data-confirm="{{ $u->is_active
                                                ? $u->name . ' จะไม่สามารถเข้าสู่ระบบได้ทันที จนกว่าจะเปิดใช้งานอีกครั้ง'
                                                : $u->name . ' จะสามารถเข้าสู่ระบบได้ทันที' }}"
                                            data-confirm-title="{{ $u->is_active ? 'ยืนยันการระงับบัญชี' : 'ยืนยันการเปิดใช้งานบัญชี' }}"
                                            data-confirm-variant="{{ $u->is_active ? 'warning' : 'success' }}"
                                            data-confirm-ok="{{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งาน' }}">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                class="btn btn-outline-{{ $u->is_active ? 'warning' : 'success' }} action-button"
                                                type="submit"
                                                title="{{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' }}"
                                                aria-label="{{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' }} {{ $u->name }}">
                                                <i class="bi bi-person-{{ $u->is_active ? 'x' : 'check' }}"
                                                    aria-hidden="true"></i>
                                            </button>
                                        </form>

                                        {{-- ลบบัญชี --}}
                                        <form method="POST"
                                            action="{{ route('users.destroy', $u) }}"
                                            class="delete-form"
                                            data-confirm="บัญชี {{ $u->name }} ({{ $u->email }}) จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบผู้ใช้งาน"
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบบัญชี">
                                            @csrf
                                            @method('DELETE')

                                            <button class="btn btn-outline-danger action-button"
                                                type="submit"
                                                title="ลบบัญชี"
                                                aria-label="ลบบัญชี {{ $u->name }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                ไม่พบข้อมูล
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $users->withQueryString()->links() }}
        </div>

    </div>
@endsection