@extends('layout')

@section('namepage')
    <div class="container">
        <h3>จัดการผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">


        <div class="d-flex justify-content-between mb-3">
            <form class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                    placeholder="ค้นหาชื่อ / อีเมล">
                <button class="btn btn-outline-secondary">ค้นหา</button>
            </form>

            <a href="{{ route('users.create') }}" class="btn btn-dark">
                + เพิ่มผู้ใช้งาน
            </a>
        </div>

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>สิทธิ์การใช้งาน</th>
                        <th>สถานะ</th>
                        <th style="width:260px">จัดการ</th>
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
                                <div class="small text-muted">📧 {{ $u->email }}</div>
                            </td>

                            <td>{{ $u->role_name }}</td>

                            <td>
                                @if ($u->is_active)
                                    <span class="badge bg-success">ใช้งานอยู่</span>
                                @else
                                    <span class="badge bg-danger">ถูกระงับ</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>

                                    {{-- ปุ่มระงับและลบ ไม่แสดงในแถวของตนเอง --}}
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggleStatus', $u) }}" class="d-inline"
                                            data-confirm="{{ $u->is_active
                                                ? $u->name . ' จะไม่สามารถเข้าสู่ระบบได้ทันที จนกว่าจะเปิดใช้งานอีกครั้ง'
                                                : $u->name . ' จะสามารถเข้าสู่ระบบได้ทันที' }}"
                                            data-confirm-title="{{ $u->is_active ? 'ยืนยันการระงับบัญชี' : 'ยืนยันการเปิดใช้งานบัญชี' }}"
                                            data-confirm-variant="{{ $u->is_active ? 'warning' : 'success' }}"
                                            data-confirm-ok="{{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งาน' }}">
                                            @csrf @method('PATCH')
                                            <button
                                                class="btn btn-sm btn-outline-{{ $u->is_active ? 'warning' : 'success' }}"
                                                type="submit">
                                                {{ $u->is_active ? 'ระงับ' : 'เปิดใช้งาน' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline"
                                            data-confirm="บัญชี {{ $u->name }} ({{ $u->email }}) จะถูกลบออกจากระบบ"
                                            data-confirm-title="ยืนยันการลบผู้ใช้งาน" 
                                            data-confirm-variant="danger"
                                            data-confirm-ok="ลบบัญชี">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                — ไม่พบข้อมูล —
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}

    </div>
@endsection
