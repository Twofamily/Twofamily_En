@extends('layout')

{{--
    หน้าสินค้า — 2 แท็บ
      ?tab=products (ค่าเริ่มต้น) : สินค้าทั้งหมด
      ?tab=types                  : ประเภทสินค้า

    ปุ่มเพิ่มสินค้า / เพิ่มประเภท เปิดเป็น modal ในหน้านี้ ไม่ย้ายหน้า
    ถ้ากรอกผิดแล้ว server ส่งกลับมา (validation error) modal เดิมจะเด้งขึ้นมาพร้อมค่าที่กรอกไว้
    — ฟอร์มแต่ละอันส่ง _modal ไปด้วย เพื่อให้รู้ว่าต้องเปิดอันไหนกลับ
--}}

@php
    $user          = auth()->user();
    $canEditProduct = $user?->canEdit('products');
    $canEditType    = $user?->canEdit('product_types');

    // modal ที่ต้องเปิดทันทีตอนโหลดหน้า: กรอกผิดแล้วเด้งกลับ หรือมาจากลิงก์ ?open=
    $openModal = match (old('_modal') ?? request('open')) {
        'product' => 'productCreateModal',
        'type'    => 'typeCreateModal',
        default   => null,
    };
@endphp

@section('namepage')
    <div class="container">
        <h3 class="mb-0">สินค้า</h3>
    </div>
@endsection

@section('content')
    <style>
        .product-tabs .nav-link {
            color: #525252;
            font-weight: 500;
        }

        .product-tabs .nav-link.active {
            color: #1a1a1a;
            font-weight: 600;
        }

        .product-tabs .count-badge {
            background: rgba(0, 0, 0, .06);
            color: #404040;
            font-weight: 600;
            margin-left: .35rem;
        }

        .product-tabs .nav-link.active .count-badge {
            background: #1a1a1a;
            color: #fff;
        }

        #productCreateModal .image-drop {
            border: 1px dashed #d4d4d4;
            border-radius: .75rem;
            background: #fafafa;
            min-height: 160px;
        }
    </style>

    <div class="container py-3">

        {{-- ===== แท็บ ===== --}}
        <ul class="nav nav-tabs product-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'products' ? 'active' : '' }}"
                   href="{{ route('products.index') }}"
                   @if ($tab === 'products') aria-current="page" @endif>
                    <i class="bi bi-boxes me-1"></i>สินค้าทั้งหมด
                    <span class="badge rounded-pill count-badge">{{ number_format($counts['products']) }}</span>
                </a>
            </li>
            @if ($canSeeTypes)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'types' ? 'active' : '' }}"
                       href="{{ route('products.index', ['tab' => 'types']) }}"
                       @if ($tab === 'types') aria-current="page" @endif>
                        <i class="bi bi-tags me-1"></i>ประเภทสินค้า
                        <span class="badge rounded-pill count-badge">{{ number_format($counts['types']) }}</span>
                    </a>
                </li>
            @endif
        </ul>

        @if ($tab === 'products')
            {{-- ================= แท็บ: สินค้าทั้งหมด ================= --}}

            <x-search-bar :action="route('products.index')"
                          placeholder="ค้นหาชื่อสินค้า / รายละเอียด / ราคา"
                          :search="$search" :per-page="$perPage"
                          :statuses="$types->pluck('name_product_type', 'id_product_type')"
                          :status="$typeId" status-name="type" status-label="ประเภท"
                          :ranges="$ranges" />

            @if ($canEditProduct)
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="button" class="btn btn-dark text-nowrap"
                            data-bs-toggle="modal" data-bs-target="#productCreateModal">
                        <i class="bi bi-plus-lg me-1"></i>เพิ่มสินค้า
                    </button>
                </div>
            @endif

            <div class="table-responsive shadow-sm rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;" class="text-center">รูปภาพ</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภท</th>
                            <th class="text-end" style="width:140px;">ราคาต่อหน่วย (บาท / คิว)</th>
                            @if ($canEditProduct)
                                <th class="text-center" style="width:80px;">จัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $p)
                            <tr>
                                <td class="text-center">
                                    @if ($p->image)
                                        <img src="{{ asset('storage/' . $p->image) }}"
                                             alt="{{ $p->name_product }}"
                                             class="rounded border shadow-sm"
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    @else
                                        <div class="bg-light text-muted rounded d-flex align-items-center justify-content-center border mx-auto"
                                             style="width: 60px; height: 60px; font-size: 0.75rem;">
                                            ไม่มีรูป
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $p->name_product }}</div>
                                    @if ($p->detail_product)
                                        <div class="small text-muted">{{ Str::limit($p->detail_product, 80) }}</div>
                                    @endif
                                </td>

                                <td>{{ $p->type->name_product_type ?? '-' }}</td>

                                <td class="text-end">{{ number_format((float) $p->unit_price, 2) }}</td>

                                @if ($canEditProduct)
                                    <td class="text-center">
                                        <x-row-actions :edit="route('products.edit', $p)"
                                                       :delete="route('products.destroy', $p)"
                                                       :label="$p->name_product" />
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canEditProduct ? 5 : 4 }}" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    ไม่พบข้อมูล
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                @include('partials.pagination-footer', ['paginator' => $products])
            </div>

        @else
            {{-- ================= แท็บ: ประเภทสินค้า ================= --}}

            <x-search-bar :action="route('products.index', ['tab' => 'types'])"
                          placeholder="ค้นหาชื่อประเภทสินค้า"
                          :search="$search" :per-page="$perPage">
                {{-- GET form ไม่ส่ง query ที่อยู่ใน action ต้องแนบแท็บเป็น hidden เอง --}}
                <input type="hidden" name="tab" value="types">
            </x-search-bar>

            @if ($canEditType)
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="button" class="btn btn-dark text-nowrap"
                            data-bs-toggle="modal" data-bs-target="#typeCreateModal">
                        <i class="bi bi-plus-lg me-1"></i>เพิ่มประเภทสินค้า
                    </button>
                </div>
            @endif

            <div class="table-responsive shadow-sm rounded-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ชื่อประเภทสินค้า</th>
                            <th class="text-center" style="width:160px;">สินค้าที่ใช้</th>
                            @if ($canEditType)
                                <th class="text-center" style="width:80px;">จัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productTypes as $t)
                            <tr>
                                <td class="fw-semibold">{{ $t->name_product_type }}</td>

                                <td class="text-center">
                                    @if ($t->products_count > 0)
                                        <a href="{{ route('products.index', ['type' => $t->id_product_type]) }}"
                                           class="text-decoration-none" title="ดูสินค้าในประเภทนี้">
                                            {{ number_format($t->products_count) }} รายการ
                                        </a>
                                    @else
                                        <span class="text-muted">0 รายการ</span>
                                    @endif
                                </td>

                                @if ($canEditType)
                                    <td class="text-center">
                                        {{-- มีสินค้าใช้อยู่ ลบไม่ได้ (controller กันไว้อีกชั้น) --}}
                                        <x-row-actions :edit="route('product_types.edit', $t)"
                                                       :delete="$t->products_count > 0 ? null : route('product_types.destroy', $t)"
                                                       :delete-disabled="$t->products_count > 0 ? 'มีสินค้าใช้ประเภทนี้อยู่' : null"
                                                       :label="$t->name_product_type" />
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canEditType ? 3 : 2 }}" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    ไม่พบข้อมูล
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                @include('partials.pagination-footer', ['paginator' => $productTypes])
            </div>
        @endif
    </div>

    {{-- ================= Modal: เพิ่มสินค้า ================= --}}
    @if ($canEditProduct)
        <div class="modal fade" id="productCreateModal" tabindex="-1" aria-labelledby="productCreateTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data"
                      class="modal-content border-0 shadow"
                      data-confirm="ต้องการบันทึกสินค้านี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf
                    <input type="hidden" name="_modal" value="product">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="productCreateTitle">เพิ่มสินค้าใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
                            {{-- รูปสินค้า --}}
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="image_input">รูปถ่ายสินค้า</label>

                                <div class="image-drop d-flex align-items-center justify-content-center mb-2 p-2">
                                    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                         id="product_preview" class="img-fluid rounded d-none"
                                         style="max-height: 200px; object-fit: cover;" alt="ตัวอย่างรูปสินค้า">
                                    <div id="placeholder_box" class="text-muted small text-center">
                                        <i class="bi bi-image fs-2 d-block mb-1"></i>
                                        ยังไม่ได้เลือกรูปภาพ
                                    </div>
                                </div>

                                <input type="file" name="image" id="image_input"
                                       class="form-control @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/webp">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    JPG, PNG, WEBP ไม่เกิน 2MB
                                    @if ($errors->any() && old('_modal') === 'product')
                                        · <span class="text-danger">กรุณาเลือกรูปใหม่อีกครั้ง</span>
                                    @endif
                                </div>
                            </div>

                            {{-- ข้อมูลสินค้า --}}
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label" for="name_product">ชื่อสินค้า</label>
                                    <input type="text" name="name_product" id="name_product"
                                           value="{{ old('_modal') === 'product' ? old('name_product') : '' }}"
                                           class="form-control @error('name_product') is-invalid @enderror"
                                           placeholder="เช่น หินกรวด" maxlength="45" required
                                           data-required-msg="กรุณาใส่ชื่อสินค้า">
                                    @error('name_product')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="product_type_id">ประเภทสินค้า</label>
                                    <select name="product_type_id" id="product_type_id"
                                            class="form-select @error('product_type_id') is-invalid @enderror"
                                            required data-required-msg="กรุณาเลือกประเภทสินค้า">
                                        <option value="">— เลือกประเภทสินค้า —</option>
                                        @foreach ($types as $t)
                                            <option value="{{ $t->id_product_type }}"
                                                @selected(old('_modal') === 'product' && old('product_type_id') == $t->id_product_type)>
                                                {{ $t->name_product_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_type_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if ($types->isEmpty())
                                        <div class="form-text text-danger">
                                            ยังไม่มีประเภทสินค้า — เพิ่มในแท็บประเภทสินค้าก่อน
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="unit_price">ราคาต่อหน่วย (บาท / คิว)</label>
                                    <input type="number" name="unit_price" id="unit_price"
                                           value="{{ old('_modal') === 'product' ? old('unit_price') : '' }}"
                                           class="form-control @error('unit_price') is-invalid @enderror"
                                           placeholder="เช่น 390" min="0" step="0.01" required
                                           data-required-msg="กรุณาใส่ราคาต่อหน่วย">
                                    @error('unit_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-0">
                                    <label class="form-label" for="detail_product">รายละเอียด</label>
                                    <textarea name="detail_product" id="detail_product" rows="3"
                                              class="form-control @error('detail_product') is-invalid @enderror"
                                              placeholder="เช่น ใช้สำหรับงานก่อสร้าง หรือถนน">{{ old('_modal') === 'product' ? old('detail_product') : '' }}</textarea>
                                    @error('detail_product')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i>บันทึกสินค้า
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ================= Modal: เพิ่มประเภทสินค้า ================= --}}
    @if ($canSeeTypes && $canEditType)
        <div class="modal fade" id="typeCreateModal" tabindex="-1" aria-labelledby="typeCreateTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('product_types.store') }}"
                      class="modal-content border-0 shadow"
                      data-confirm="ต้องการบันทึกประเภทสินค้านี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล"
                      data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf
                    <input type="hidden" name="_modal" value="type">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="typeCreateTitle">เพิ่มประเภทสินค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label" for="name_product_type">ชื่อประเภทสินค้า</label>
                        <input type="text" name="name_product_type" id="name_product_type"
                               value="{{ old('_modal') === 'type' ? old('name_product_type') : '' }}"
                               class="form-control @error('name_product_type') is-invalid @enderror"
                               placeholder="เช่น ดิน, ทราย, หิน" maxlength="100" required
                               data-required-msg="กรุณาใส่ประเภทสินค้า">
                        @error('name_product_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">ใช้จัดกลุ่มสินค้า และเป็นตัวกรองในแท็บสินค้าทั้งหมด</div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark">
                            <i class="bi bi-check-lg me-1"></i>บันทึกประเภท
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* ---------- ตัวอย่างรูปก่อนอัปโหลด ---------- */
            const imageInput = document.getElementById('image_input');
            const preview = document.getElementById('product_preview');
            const placeholder = document.getElementById('placeholder_box');

            if (imageInput && preview) {
                imageInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        preview.src = ev.target.result;
                        preview.classList.remove('d-none');
                        if (placeholder) placeholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                });
            }

            /* ---------- เปิด modal ให้อัตโนมัติ (กรอกผิดแล้วเด้งกลับ / ลิงก์ ?open=) ---------- */
            const openId = @json($openModal);
            const openEl = openId ? document.getElementById(openId) : null;

            if (openEl) {
                bootstrap.Modal.getOrCreateInstance(openEl).show();

                // ลบ ?open= ออกจาก URL กดรีเฟรชแล้วจะได้ไม่เด้งซ้ำ
                const url = new URL(window.location.href);
                if (url.searchParams.has('open')) {
                    url.searchParams.delete('open');
                    history.replaceState(null, '', url);
                }
            }

            /* ---------- เปิด modal แล้วโฟกัสช่องแรก ---------- */
            document.querySelectorAll('#productCreateModal, #typeCreateModal').forEach(function (el) {
                el.addEventListener('shown.bs.modal', function () {
                    const first = el.querySelector('input[type="text"]');
                    if (first) first.focus();
                });
            });
        });
    </script>
@endsection
