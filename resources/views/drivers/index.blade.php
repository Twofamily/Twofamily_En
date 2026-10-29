@extends('layout')

@section('namepage')
    <div class="container">
        <h3>พนักงานขับรถทั้งหมด</h3>
    </div>
@endsection

@php
    /*
     | เพิ่ม / แก้ไขพนักงานขับรถในหน้าต่าง (modal) ของหน้านี้
     | กรอกผิดแล้วเด้งกลับ → เปิดหน้าต่างเดิมให้อัตโนมัติ พร้อมค่าที่กรอกไว้
     */
    // ผู้ใช้สิทธิ์ดูอย่างเดียว: ไม่แสดงปุ่มเพิ่ม / แก้ไข / ลบ (ฝั่ง server ก็กันไว้แล้ว)
    $canEdit = auth()->user()->canEdit('drivers');

    $createHasOld = $errors->createDriver->any();
    $openCreate   = $canEdit && ($createHasOld || request('modal') === 'create');

    $editHasOld = $errors->updateDriver->any();
    $openEditId = $canEdit ? ($editHasOld ? (int) old('_driver_id') : (int) request('edit')) : 0;
    $openEditDriver = $openEditId ? \App\Models\Driver::find($openEditId) : null;

    $editDataOf = fn (\App\Models\Driver $d) => [
        'id'               => $d->id_driver,
        'title'            => $d->full_name,
        'url'              => route('drivers.update', $d),
        'image'            => $d->citizen_image ? asset('storage/' . $d->citizen_image) : null,
        'fname_driver'     => $d->fname_driver,
        'lname_driver'     => $d->lname_driver,
        'phone_driver'     => $d->phone_driver,
        'citizenid_driver' => $d->citizenid_driver,
        'address_no'       => $d->address_no,
        'moo'              => $d->moo,
        'address_detail'   => $d->address_detail,
        'province'         => $d->province,
        'district'         => $d->district,
        'subdistrict'      => $d->subdistrict,
        'zipcode'          => $d->zipcode,
    ];

    $oldEdit   = $editHasOld ? \Illuminate\Support\Arr::except(old(), ['_token', '_method', '_driver_id']) : null;
    $keepQuery = array_filter(request()->only('page', 'search', 'per_page'), fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
    <div class="container py-3">

        <x-search-bar :action="route('drivers.index')"
                      placeholder="ค้นหาชื่อ / นามสกุล / เบอร์โทร / เลขบัตรประชาชน"
                      :search="$search" :per-page="$perPage" />

        <div class="d-flex justify-content-end mb-3">
            @if ($canEdit)
                <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#createDriverModal">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มพนักงานขับรถ
                </button>
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
                                               :delete="$canEdit ? route('drivers.destroy', $d) : null"
                                               :label="$d->fname_driver . ' ' . $d->lname_driver">
                                    @if ($canEdit)
                                        <li>
                                            <button type="button" class="dropdown-item" data-driver-edit='@json($editDataOf($d))'>
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                                            </button>
                                        </li>
                                    @endif
                                </x-row-actions>
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

    @if ($canEdit)
        {{-- ==================== หน้าต่างเพิ่ม ==================== --}}
        <div class="modal fade" id="createDriverModal" tabindex="-1" aria-labelledby="createDriverTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('drivers.store') }}" enctype="multipart/form-data"
                      data-driver-form
                      data-confirm="ต้องการบันทึกข้อมูลพนักงานขับรถรายนี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createDriverTitle"><i class="bi bi-person-plus me-1"></i>เพิ่มพนักงานขับรถ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.modal-errors', ['bag' => $errors->createDriver])
                        @include('drivers._fields', ['p' => 'c_', 'v' => $createHasOld ? old() : [], 'bag' => $errors->createDriver])
                    </div>
                    <div class="modal-footer">
                        <span class="small text-muted me-auto"><span class="req-star">*</span> = จำเป็นต้องกรอก</span>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ==================== หน้าต่างแก้ไข (ใช้ร่วมทุกแถว) ==================== --}}
        <div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="#" enctype="multipart/form-data" data-driver-form
                      data-confirm="ข้อมูลพนักงานขับรถจะถูกอัปเดตทันที" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf @method('PUT')
                    <input type="hidden" name="_driver_id" data-edit-id>
                    @foreach ($keepQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDriverTitle">
                            <i class="bi bi-pencil-square me-1"></i>แก้ไขพนักงานขับรถ
                            <span class="text-muted fw-normal fs-6" data-edit-name></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.modal-errors', ['bag' => $errors->updateDriver])
                        @include('drivers._fields', ['p' => 'e_', 'v' => [], 'bag' => $errors->updateDriver])
                    </div>
                    <div class="modal-footer">
                        <span class="small text-muted me-auto">ไม่เลือกรูปใหม่ = ใช้รูปเดิม</span>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>

        @include('partials.modal-form')
        @include('partials.thai-address')

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const setAddress = new Map();

                // รูปตัวอย่าง: เลือกไฟล์ใหม่แล้วแสดงทันที
                function showImage(form, src) {
                    const img = form.querySelector('[data-image-preview]');
                    img.classList.toggle('d-none', !src);
                    form.querySelector('[data-image-empty]').classList.toggle('d-none', !!src);
                    if (src) img.src = src;
                }

                document.querySelectorAll('[data-driver-form]').forEach((form) => {
                    setAddress.set(form, ThaiAddress.setup(form));
                    form.querySelector('[data-image-input]').addEventListener('change', (e) => {
                        const file = e.target.files[0];
                        if (!file) return;
                        const reader = new FileReader();
                        reader.onload = (ev) => showImage(form, ev.target.result);
                        reader.readAsDataURL(file);
                    });
                });

                const editModalEl = document.getElementById('editDriverModal');
                const editForm    = editModalEl.querySelector('form');

                function open(driver, old) {
                    if (!old) ModalForm.clearErrors(editModalEl);
                    const v = Object.assign({}, driver, old || {});

                    editModalEl.querySelector('[data-edit-name]').textContent = '— ' + driver.title;
                    editForm.querySelector('[data-edit-id]').value = driver.id;
                    editForm.action = driver.url;
                    editForm.querySelector('[data-image-input]').value = '';
                    showImage(editForm, driver.image);

                    ModalForm.fill(editForm, {
                        fname_driver: v.fname_driver, lname_driver: v.lname_driver,
                        phone_driver: v.phone_driver, citizenid_driver: v.citizenid_driver,
                        address_no: v.address_no, moo: v.moo, address_detail: v.address_detail,
                    });
                    setAddress.get(editForm)(v);
                    ModalForm.open(editModalEl);
                }

                document.querySelectorAll('[data-driver-edit]').forEach((btn) => {
                    btn.addEventListener('click', () => open(JSON.parse(btn.dataset.driverEdit)));
                });

                @if ($openCreate)
                    ModalForm.open(document.getElementById('createDriverModal'));
                @elseif ($openEditDriver)
                    open(@json($editDataOf($openEditDriver)), @json($oldEdit));
                @endif
            });
        </script>
    @endif
@endsection
