@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ลูกค้าทั้งหมด</h3>
    </div>
@endsection

@php
    /*
     | เพิ่ม / แก้ไขลูกค้าทำในหน้าต่าง (modal) ของหน้านี้ ไม่ต้องย้ายหน้า
     | กรอกผิดแล้วเด้งกลับมา → เปิดหน้าต่างเดิมให้อัตโนมัติ พร้อมค่าที่กรอกไว้และข้อความแจ้ง
     */
    $canEdit = auth()->user()->canEdit();

    $createHasOld = $errors->createCustomer->any();
    $openCreate   = $canEdit && ($createHasOld || request('modal') === 'create');

    $editHasOld   = $errors->updateCustomer->any();
    $openEditId   = $canEdit ? ($editHasOld ? (int) old('_customer_id') : (int) request('edit')) : 0;
    $openEditCustomer = $openEditId ? \App\Models\Customer::find($openEditId) : null;
    if ($openEditCustomer?->isWalkIn()) {
        $openEditCustomer = null;
    }

    // ค่าที่หน้าต่างแก้ไขใช้เติมฟอร์มของลูกค้าแต่ละราย
    $editDataOf = function (\App\Models\Customer $c) {
        $isBranch = $c->isCompany() && $c->branch_code && $c->branch_code !== \App\Models\Customer::HEAD_OFFICE;

        return [
            'id'             => $c->id_customer,
            'title'          => $c->name_customer,
            'url'            => route('customers.update', $c),
            'customer_type'  => $c->customer_type ?: 'person',
            'name_prefix'    => $c->name_prefix,
            'first_name'     => $c->first_name,
            'last_name'      => $c->last_name,
            'name_customer'  => $c->isCompany() ? $c->name_customer : '',
            'branch_kind'    => $isBranch ? 'branch' : 'head',
            'branch_no'      => $isBranch ? $c->branch_code : '',
            'contact_name'   => $c->contact_name,
            'tax_id'         => $c->tax_id,
            'phone_customer' => $c->phone_customer,
            'email_customer' => $c->email_customer,
            'address_no'     => $c->address_no,
            'moo'            => $c->moo,
            'address_detail' => $c->address_detail,
            'soi'            => $c->soi,
            'road'           => $c->road,
            'province'       => $c->province,
            'district'       => $c->district,
            'subdistrict'    => $c->subdistrict,
            'zipcode'        => $c->zipcode,
        ];
    };

    // ค่าที่กรอกไว้ก่อนเด้งกลับ (หน้าต่างแก้ไข)
    $oldEdit = $editHasOld ? \Illuminate\Support\Arr::except(old(), ['_token', '_method', '_customer_id']) : null;

    // ส่งต่อ หน้า / คำค้น / ตัวกรอง ไปกับฟอร์มแก้ไข จะได้กลับมาหน้าเดิมหลังบันทึก
    $keepQuery = array_filter(request()->only('page', 'search', 'type', 'per_page'), fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
    <style>
        .customer-section {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--bs-secondary-color);
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: .4rem;
            margin-bottom: .9rem;
        }
    </style>

    <div class="container py-3">

        <x-search-bar :action="route('customers.index')"
                      placeholder="ค้นหาชื่อ / ผู้ติดต่อ / เลขภาษี / เบอร์โทร / อีเมล / ที่อยู่"
                      :search="$search" :per-page="$perPage"
                      :statuses="$typeLabels" :status="$type" status-name="type" status-label="ประเภทลูกค้า" />

        @if ($canEdit)
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                    <i class="bi bi-plus-lg me-1"></i>
                    เพิ่มลูกค้า
                </button>
            </div>
        @endif

        <div class="table-responsive shadow-sm rounded-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ชื่อลูกค้า</th>
                        <th>ประเภท</th>
                        <th>ติดต่อ</th>
                        <th>ที่ตั้ง</th>
                        <th class="action-col text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $c->name_customer }}</div>
                                @if ($c->isWalkIn())
                                    <div class="small text-muted">ลูกค้าของระบบสำหรับขายสด — แก้ไข/ลบไม่ได้</div>
                                @endif
                                @if ($c->tax_id)
                                    <div class="small text-muted">
                                        เลขภาษี {{ $c->formattedTaxId() }}
                                        @if ($c->branchLabel())
                                            · {{ $c->branchLabel() }}
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if ($c->isCompany())
                                    <span class="badge text-bg-light border"><i class="bi bi-building me-1"></i>บริษัท</span>
                                @else
                                    <span class="badge text-bg-light border"><i class="bi bi-person me-1"></i>บุคคล</span>
                                @endif
                            </td>

                            <td>
                                @if ($c->contact_name)
                                    <div class="small">{{ $c->contact_name }}</div>
                                @endif
                                <div>{{ $c->phone_customer ?: '-' }}</div>
                                @if ($c->email_customer)
                                    <div class="small text-muted">
                                        <i class="bi bi-envelope me-1"></i>{{ $c->email_customer }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if ($c->province)
                                    <div>{{ $c->province }}</div>
                                    @if ($c->district)
                                        <div class="small text-muted">{{ $c->district }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-center">
                                @if ($canEdit && ! $c->isWalkIn())
                                    <x-row-actions :show="route('customers.show', $c->id_customer)"
                                                   :delete="route('customers.destroy', $c->id_customer)"
                                                   :label="$c->name_customer">
                                        <li>
                                            <button type="button" class="dropdown-item" data-customer-edit='@json($editDataOf($c))'>
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                                            </button>
                                        </li>
                                    </x-row-actions>
                                @else
                                    <x-row-actions :show="route('customers.show', $c->id_customer)" :label="$c->name_customer" />
                                @endif
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

        @include('partials.pagination-footer', ['paginator' => $customers])

    </div>

    @if ($canEdit)
        {{-- ==================== หน้าต่างเพิ่มลูกค้า ==================== --}}
        <div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('customers.store') }}" data-customer-form
                      data-confirm="ต้องการบันทึกข้อมูลลูกค้ารายนี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="createCustomerTitle">
                            <i class="bi bi-person-plus me-1"></i>เพิ่มลูกค้า
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        @if ($createHasOld)
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 small">
                                    @foreach ($errors->createCustomer->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @include('customers._fields', [
                            'p'   => 'c_',
                            'v'   => $createHasOld ? old() : [],
                            'bag' => $errors->createCustomer,
                        ])
                    </div>

                    <div class="modal-footer">
                        <span class="small text-muted me-auto"><span class="req-star">*</span> = จำเป็นต้องกรอก</span>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ==================== หน้าต่างแก้ไขลูกค้า (ใช้ร่วมทุกแถว) ==================== --}}
        <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="#" data-customer-form
                      data-confirm="ข้อมูลลูกค้าจะถูกอัปเดตทันที เอกสารที่ออกหลังจากนี้จะใช้ข้อมูลใหม่"
                      data-confirm-title="ยืนยันการแก้ไขข้อมูลลูกค้า" data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf @method('PUT')
                    <input type="hidden" name="_customer_id" data-edit-id>
                    @foreach ($keepQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="modal-header">
                        <h5 class="modal-title" id="editCustomerTitle">
                            <i class="bi bi-pencil-square me-1"></i>แก้ไขข้อมูลลูกค้า
                            <span class="text-muted fw-normal fs-6" data-edit-name></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        @if ($editHasOld)
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 small">
                                    @foreach ($errors->updateCustomer->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @include('customers._fields', [
                            'p'   => 'e_',
                            'v'   => [],
                            'bag' => $errors->updateCustomer,
                        ])
                    </div>

                    <div class="modal-footer">
                        <span class="small text-muted me-auto"><span class="req-star">*</span> = จำเป็นต้องกรอก</span>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                /* ---------- ที่อยู่: จังหวัด → อำเภอ → ตำบล → รหัสไปรษณีย์ ---------- */
                let addressData = null;
                function loadAddress() {
                    addressData = addressData || fetch(@json(asset('data/thai-address.json')))
                        .then((res) => res.json())
                        .catch(() => []);
                    return addressData;
                }

                const districtsOf = (p) => (p && (p.districts || p.amphure || p.district)) || [];
                const subsOf      = (d) => (d && (d.sub_districts || d.tambon || d.subdistricts)) || [];

                function resetSelect(select, placeholder) {
                    select.innerHTML = '';
                    select.add(new Option(placeholder, ''));
                }

                // ตั้งค่าที่เลือก ถ้าไม่มีในรายการ (ข้อมูลเก่าสะกดต่างกัน) ก็เพิ่มตัวเลือกนั้นไว้ ไม่ให้ค่าหาย
                function choose(select, value) {
                    if (!value) { select.value = ''; return; }
                    if (![...select.options].some((o) => o.value === value)) {
                        select.add(new Option(value, value));
                    }
                    select.value = value;
                }

                function setupAddress(form) {
                    const box = form.querySelector('[data-address]');
                    const prov = box.querySelector('[data-addr="province"]');
                    const dist = box.querySelector('[data-addr="district"]');
                    const sub  = box.querySelector('[data-addr="subdistrict"]');
                    const zip  = box.querySelector('[data-addr="zipcode"]');
                    let data = [];

                    function fillDistricts() {
                        resetSelect(dist, '— เลือกอำเภอ —');
                        resetSelect(sub, '— เลือกตำบล —');
                        const p = data.find((x) => x.name_th === prov.value);
                        districtsOf(p).forEach((d) => dist.add(new Option(d.name_th, d.name_th)));
                        dist.disabled = !prov.value;
                        sub.disabled = true;
                    }

                    function fillSubs() {
                        resetSelect(sub, '— เลือกตำบล —');
                        const p = data.find((x) => x.name_th === prov.value);
                        const d = districtsOf(p).find((x) => x.name_th === dist.value);
                        subsOf(d).forEach((s) => {
                            const opt = new Option(s.name_th, s.name_th);
                            opt.dataset.zip = s.zip_code ?? s.zipcode ?? '';
                            sub.add(opt);
                        });
                        sub.disabled = !dist.value;
                    }

                    prov.addEventListener('change', () => { fillDistricts(); zip.value = ''; });
                    dist.addEventListener('change', () => { fillSubs(); zip.value = ''; });
                    sub.addEventListener('change', () => {
                        const z = sub.selectedOptions[0]?.dataset.zip;
                        if (z) zip.value = z;
                    });

                    const ready = loadAddress().then((d) => {
                        data = d || [];
                        resetSelect(prov, '— เลือกจังหวัด —');
                        data.forEach((p) => prov.add(new Option(p.name_th, p.name_th)));
                    });

                    // ใส่ที่อยู่ที่บันทึกไว้ (หน้าต่างแก้ไข / ค่าที่กรอกไว้ก่อนเด้งกลับ)
                    return function set(v) {
                        return ready.then(() => {
                            choose(prov, v.province || '');
                            fillDistricts();
                            choose(dist, v.district || '');
                            dist.disabled = !prov.value;
                            fillSubs();
                            choose(sub, v.subdistrict || '');
                            sub.disabled = !dist.value;
                            zip.value = v.zipcode || '';
                        });
                    };
                }

                /* ---------- ประเภทลูกค้า / สาขา: แสดงเฉพาะช่องที่เกี่ยวข้อง ---------- */
                function applyType(form) {
                    const type = form.querySelector('[data-customer-type]:checked')?.value || 'person';

                    form.querySelectorAll('[data-type-block]').forEach((block) => {
                        const active = block.dataset.typeBlock === type;
                        block.classList.toggle('d-none', !active);
                        // ช่องที่ซ่อนไม่ต้องตรวจ และไม่ส่งไปบันทึก
                        block.querySelectorAll('input, select, textarea').forEach((el) => el.disabled = !active);
                    });

                    applyBranch(form);
                }

                function applyBranch(form) {
                    const kind  = form.querySelector('[data-branch-kind]');
                    const box   = form.querySelector('[data-branch-no-box]');
                    const show  = !kind.disabled && kind.value === 'branch';
                    box.classList.toggle('d-none', !show);
                    box.querySelector('input').disabled = !show;
                }

                const setters = new Map();
                document.querySelectorAll('[data-customer-form]').forEach((form) => {
                    form.querySelectorAll('[data-customer-type]').forEach((r) => r.addEventListener('change', () => applyType(form)));
                    form.querySelector('[data-branch-kind]').addEventListener('change', () => applyBranch(form));
                    applyType(form);
                    setters.set(form, setupAddress(form));
                });

                /* ---------- หน้าต่างเพิ่ม ---------- */
                const createForm = document.querySelector('#createCustomerModal form');
                const createAddr = createForm.querySelector('[data-address]');
                setters.get(createForm)({
                    province:    createAddr.querySelector('[data-addr="province"]').dataset.value,
                    district:    createAddr.querySelector('[data-addr="district"]').dataset.value,
                    subdistrict: createAddr.querySelector('[data-addr="subdistrict"]').dataset.value,
                    zipcode:     createAddr.querySelector('[data-addr="zipcode"]').value,
                });

                /* ---------- หน้าต่างแก้ไข ---------- */
                const editModalEl = document.getElementById('editCustomerModal');
                const editModal   = bootstrap.Modal.getOrCreateInstance(editModalEl);
                const editForm    = editModalEl.querySelector('form');

                function fill(customer, old) {
                    const v = Object.assign({}, customer, old || {});

                    editModalEl.querySelector('[data-edit-name]').textContent = '— ' + customer.title;
                    editModalEl.querySelector('[data-edit-id]').value = customer.id;
                    editForm.action = customer.url;

                    editForm.querySelector('[data-customer-type][value="' + (v.customer_type || 'person') + '"]').checked = true;
                    applyType(editForm);   // เปิดช่องทั้งหมดของประเภทนั้นก่อน แล้วค่อยเติมค่า

                    ['name_prefix', 'first_name', 'last_name', 'name_customer', 'branch_kind', 'branch_no',
                     'contact_name', 'tax_id', 'phone_customer', 'email_customer',
                     'address_no', 'moo', 'address_detail', 'soi', 'road'].forEach((name) => {
                        const el = editForm.elements[name];
                        if (!el) return;
                        if (el.tagName === 'SELECT') {
                            choose(el, v[name] || (name === 'branch_kind' ? 'head' : ''));
                        } else {
                            el.value = v[name] ?? '';
                        }
                    });

                    applyType(editForm);
                    return setters.get(editForm)(v);
                }

                function open(customer, old) {
                    // ล้างกรอบแดงจากการเปิดครั้งก่อน (ยกเว้นตอนเด้งกลับพร้อมข้อผิดพลาด)
                    if (!old) {
                        editForm.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
                        editModalEl.querySelector('.alert-danger')?.remove();
                    }
                    fill(customer, old);
                    editModal.show();
                }

                document.querySelectorAll('[data-customer-edit]').forEach((btn) => {
                    btn.addEventListener('click', () => open(JSON.parse(btn.dataset.customerEdit)));
                });

                // เปิดหน้าต่างให้เองเมื่อกรอกผิดแล้วเด้งกลับ หรือมาจากลิงก์ ?modal=create / ?edit=ID
                @if ($openCreate)
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('createCustomerModal')).show();
                @elseif ($openEditCustomer)
                    open(@json($editDataOf($openEditCustomer)), @json($oldEdit));
                @endif
            });
        </script>
    @endif
@endsection
