@extends('layout')

@section('namepage')
    <div class="container">
        <h3>จัดการผู้ใช้งาน</h3>
    </div>
@endsection

@section('content')
    <div class="container py-3">

        <x-search-bar :action="route('users.index')"
                      placeholder="ค้นหาชื่อ / อีเมล / สิทธิ์การใช้งาน"
                      :search="$search" :per-page="$perPage"
                      :statuses="$statusLabels" :status="$status" status-label="สถานะบัญชี">
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small text-muted mb-1">สิทธิ์การใช้งาน</label>
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">— ทุกสิทธิ์ —</option>
                    @foreach (\App\Models\User::roleList() as $value => $label)
                        <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-search-bar>

        <div class="d-flex justify-content-end mb-3">
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
                        <th class="text-center" style="width:80px;">จัดการ</th>
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
                                {{-- ระงับและลบ ไม่แสดงในแถวของตนเอง --}}
                                <x-row-actions :edit="route('users.edit', $u)"
                                               :delete="$u->id !== auth()->id() ? route('users.destroy', $u) : null"
                                               :label="$u->name"
                                               delete-label="ลบบัญชี"
                                               :delete-confirm="'บัญชี ' . $u->name . ' (' . $u->email . ') จะถูกลบออกจากระบบ'"
                                               delete-title="ยืนยันการลบผู้ใช้งาน" delete-ok="ลบบัญชี">
                                    @if ($u->id !== auth()->id())
                                        <x-row-actions.form :action="route('users.toggleStatus', $u)" method="PATCH"
                                                            :icon="$u->is_active ? 'bi-person-x' : 'bi-person-check'"
                                                            :variant="$u->is_active ? 'warning' : 'success'"
                                                            :confirm="$u->is_active
                                                                ? $u->name . ' จะไม่สามารถเข้าสู่ระบบได้ทันที จนกว่าจะเปิดใช้งานอีกครั้ง'
                                                                : $u->name . ' จะสามารถเข้าสู่ระบบได้ทันที'"
                                                            :confirm-title="$u->is_active ? 'ยืนยันการระงับบัญชี' : 'ยืนยันการเปิดใช้งานบัญชี'"
                                                            :confirm-ok="$u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งาน'">
                                            {{ $u->is_active ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' }}
                                        </x-row-actions.form>
                                    @endif
                                </x-row-actions>
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
            @include('partials.pagination-footer', ['paginator' => $users])
        </div>

    </div>
@endsection