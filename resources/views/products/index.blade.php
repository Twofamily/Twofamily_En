@extends('layout')

@php
    $user = auth()->user();
    $canEditProduct = $user?->canEdit('products');
    $canEditType = $user?->canEdit('product_types');
    $openModal = match (old('_modal') ?? request('open')) {
        'product' => 'productCreateModal',
        'type' => 'typeCreateModal',
        default => null,
    };
    $productEditData = fn(\App\Models\Product $p) => [
        'id' => $p->id_product,
        'title' => $p->name_product,
        'url' => route('products.update', $p),
        'image' => $p->image ? asset('storage/' . $p->image) : null,
        'name_product' => $p->name_product,
        'product_type_id' => $p->product_type_id,
        'unit_price' => (float) $p->unit_price,
        'detail_product' => $p->detail_product,
    ];
    $typeEditData = fn(\App\Models\ProductType $t) => [
        'id' => $t->id_product_type,
        'title' => $t->name_product_type,
        'url' => route('product_types.update', $t),
        'name_product_type' => $t->name_product_type,
    ];

    $productEditHasOld = $errors->updateProduct->any();
    $openProductId = $canEditProduct ? ($productEditHasOld ? (int) old('_product_id') : (int) request('edit')) : 0;
    $openProduct = $openProductId ? \App\Models\Product::find($openProductId) : null;

    $typeEditHasOld = $errors->updateType->any();
    $openTypeId = $canEditType ? ($typeEditHasOld ? (int) old('_type_id') : (int) request('edit_type')) : 0;
    $openType = $openTypeId ? \App\Models\ProductType::find($openTypeId) : null;

    $oldEdit =
        $productEditHasOld || $typeEditHasOld
            ? \Illuminate\Support\Arr::except(old(), ['_token', '_method', '_product_id', '_type_id', 'image'])
            : null;
    $keepQuery = array_filter(
        request()->only('page', 'search', 'type', 'per_page'),
        fn($v) => $v !== null && $v !== '',
    );
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
        <ul class="nav nav-tabs product-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'products' ? 'active' : '' }}" href="{{ route('products.index') }}"
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
            <x-search-bar :action="route('products.index')" placeholder="ชื่อสินค้า / รายละเอียด / ราคา" :search="$search" :per-page="$perPage"
                :statuses="$types->pluck('name_product_type', 'id_product_type')" :status="$typeId" status-name="type" status-label="ประเภท" {{-- :ranges="$ranges"  --}} />

            @if ($canEditProduct)
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal"
                        data-bs-target="#productCreateModal">
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
                                        <img src="{{ asset('storage/' . $p->image) }}" alt="{{ $p->name_product }}"
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
                                        <x-row-actions :delete="route('products.destroy', $p)" :label="$p->name_product">
                                            <li>
                                                <button type="button" class="dropdown-item"
                                                    data-product-edit='@json($productEditData($p))'>
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                                                </button>
                                            </li>
                                        </x-row-actions>
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
            <x-search-bar :action="route('products.index', ['tab' => 'types'])" placeholder="ชื่อประเภทสินค้า" :search="$search" :per-page="$perPage">
                <input type="hidden" name="tab" value="types">
            </x-search-bar>

            @if ($canEditType)
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <button type="button" class="btn btn-dark text-nowrap" data-bs-toggle="modal"
                        data-bs-target="#typeCreateModal">
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
                                        <x-row-actions :delete="$t->products_count > 0 ? null : route('product_types.destroy', $t)" :delete-disabled="$t->products_count > 0 ? 'มีสินค้าใช้ประเภทนี้อยู่' : null" :label="$t->name_product_type">
                                            <li>
                                                <button type="button" class="dropdown-item"
                                                    data-type-edit='@json($typeEditData($t))'>
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>แก้ไข
                                                </button>
                                            </li>
                                        </x-row-actions>
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

    @if ($canEditProduct)
        <div class="modal fade" id="productCreateModal" tabindex="-1" aria-labelledby="productCreateTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data"
                    class="modal-content border-0 shadow" data-confirm="ต้องการบันทึกสินค้านี้หรือไม่"
                    data-confirm-title="ยืนยันการบันทึกข้อมูล" data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf
                    <input type="hidden" name="_modal" value="product">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="productCreateTitle">เพิ่มสินค้าใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
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

                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label" for="name_product">ชื่อสินค้า</label>
                                    <input type="text" name="name_product" id="name_product"
                                        value="{{ old('_modal') === 'product' ? old('name_product') : '' }}"
                                        class="form-control @error('name_product') is-invalid @enderror"
                                        placeholder="เช่น หินกรวด" maxlength="45" required
                                        data-required-msg="กรุณากรอกชื่อสินค้า">
                                    @error('name_product')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="product_type_id">ประเภทสินค้า</label>
                                    <select name="product_type_id" id="product_type_id"
                                        class="form-select @error('product_type_id') is-invalid @enderror" required
                                        data-required-msg="กรุณาเลือกประเภทสินค้า">
                                        <option value="">— เลือกประเภทสินค้า —</option>
                                        @foreach ($types as $t)
                                            <option value="{{ $t->id_product_type }}" @selected(old('_modal') === 'product' && old('product_type_id') == $t->id_product_type)>
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
                                        data-required-msg="กรุณากรอกราคาต่อหน่วย">
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

    @if ($canEditProduct)
        <div class="modal fade" id="productEditModal" tabindex="-1" aria-labelledby="productEditTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="#" enctype="multipart/form-data" class="modal-content border-0 shadow"
                    data-confirm="ข้อมูลสินค้าจะถูกอัปเดตทันที" data-confirm-title="ยืนยันการแก้ไขสินค้า"
                    data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf @method('PUT')
                    <input type="hidden" name="_product_id" data-edit-id>
                    @foreach ($keepQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="productEditTitle">
                            แก้ไขสินค้า <span class="text-muted fw-normal fs-6" data-edit-name></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        @include('partials.modal-errors', ['bag' => $errors->updateProduct])
                        @php $pe = $errors->updateProduct; @endphp

                        <div class="row g-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="e_image">รูปถ่ายสินค้า</label>
                                <div class="image-drop d-flex align-items-center justify-content-center mb-2 p-2">
                                    <img class="img-fluid rounded d-none" style="max-height: 200px; object-fit: cover;"
                                        alt="รูปสินค้า" data-image-preview>
                                    <div class="text-muted small text-center" data-image-empty>
                                        <i class="bi bi-image fs-2 d-block mb-1"></i>ยังไม่มีรูปภาพ
                                    </div>
                                </div>
                                <input type="file" name="image" id="e_image"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="form-control {{ $pe->has('image') ? 'is-invalid' : '' }}" data-image-input>
                                <div class="form-text">ไม่เลือกรูปใหม่ = ใช้รูปเดิม · JPG, PNG, WEBP ไม่เกิน 2MB</div>
                            </div>

                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label" for="e_name_product">ชื่อสินค้า</label>
                                    <input type="text" name="name_product" id="e_name_product" maxlength="45"
                                        required class="form-control {{ $pe->has('name_product') ? 'is-invalid' : '' }}"
                                        data-required-msg="กรุณากรอกชื่อสินค้า">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="e_product_type_id">ประเภทสินค้า</label>
                                    <select name="product_type_id" id="e_product_type_id" required
                                        class="form-select {{ $pe->has('product_type_id') ? 'is-invalid' : '' }}"
                                        data-required-msg="กรุณาเลือกประเภทสินค้า">
                                        <option value="">— เลือกประเภทสินค้า —</option>
                                        @foreach ($types as $t)
                                            <option value="{{ $t->id_product_type }}">{{ $t->name_product_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="e_unit_price">ราคาต่อหน่วย (บาท / คิว)</label>
                                    <input type="number" name="unit_price" id="e_unit_price" min="0"
                                        step="0.01" required
                                        class="form-control {{ $pe->has('unit_price') ? 'is-invalid' : '' }}"
                                        data-required-msg="กรุณากรอกราคาต่อหน่วย">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label" for="e_detail_product">รายละเอียด</label>
                                    <textarea name="detail_product" id="e_detail_product" rows="3"
                                        class="form-control {{ $pe->has('detail_product') ? 'is-invalid' : '' }}"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i
                                class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canSeeTypes && $canEditType)
        <div class="modal fade" id="typeEditModal" tabindex="-1" aria-labelledby="typeEditTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content border-0 shadow"
                    data-confirm="ชื่อประเภทสินค้าจะถูกเปลี่ยนทันที" data-confirm-title="ยืนยันการแก้ไขข้อมูล"
                    data-confirm-ok="บันทึก" data-confirm-variant="primary">
                    @csrf @method('PUT')
                    <input type="hidden" name="_type_id" data-edit-id>

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="typeEditTitle">แก้ไขประเภทสินค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>

                    <div class="modal-body">
                        @include('partials.modal-errors', ['bag' => $errors->updateType])
                        <label class="form-label" for="e_name_product_type">ชื่อประเภทสินค้า</label>
                        <input type="text" name="name_product_type" id="e_name_product_type" maxlength="100" required
                            class="form-control {{ $errors->updateType->has('name_product_type') ? 'is-invalid' : '' }}"
                            data-required-msg="กรุณากรอกประเภทสินค้า">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-dark"><i
                                class="bi bi-check-lg me-1"></i>บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('partials.modal-form')

    @if ($canSeeTypes && $canEditType)
        <div class="modal fade" id="typeCreateModal" tabindex="-1" aria-labelledby="typeCreateTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('product_types.store') }}" class="modal-content border-0 shadow"
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
                            data-required-msg="กรุณากรอกประเภทสินค้า">
                        @error('name_product_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('image_input');
            const preview = document.getElementById('product_preview');
            const placeholder = document.getElementById('placeholder_box');

            if (imageInput && preview) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        preview.src = ev.target.result;
                        preview.classList.remove('d-none');
                        if (placeholder) placeholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                });
            }

            const openId = @json($openModal);
            const openEl = openId ? document.getElementById(openId) : null;

            if (openEl) {
                bootstrap.Modal.getOrCreateInstance(openEl).show();

                const url = new URL(window.location.href);
                if (url.searchParams.has('open')) {
                    url.searchParams.delete('open');
                    history.replaceState(null, '', url);
                }
            }

            const productEditEl = document.getElementById('productEditModal');
            if (productEditEl) {
                const form = productEditEl.querySelector('form');
                const img = form.querySelector('[data-image-preview]');
                const showImage = (src) => {
                    img.classList.toggle('d-none', !src);
                    form.querySelector('[data-image-empty]').classList.toggle('d-none', !!src);
                    if (src) img.src = src;
                };

                form.querySelector('[data-image-input]').addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (ev) => showImage(ev.target.result);
                    reader.readAsDataURL(file);
                });

                const openProduct = (product, old) => {
                    if (!old) ModalForm.clearErrors(productEditEl);
                    const v = Object.assign({}, product, old || {});
                    productEditEl.querySelector('[data-edit-name]').textContent = '— ' + product.title;
                    form.querySelector('[data-edit-id]').value = product.id;
                    form.action = product.url;
                    form.querySelector('[data-image-input]').value = '';
                    showImage(product.image);
                    ModalForm.fill(form, {
                        name_product: v.name_product,
                        product_type_id: v.product_type_id,
                        unit_price: v.unit_price,
                        detail_product: v.detail_product,
                    });
                    ModalForm.open(productEditEl);
                };

                document.querySelectorAll('[data-product-edit]').forEach((btn) =>
                    btn.addEventListener('click', () => openProduct(JSON.parse(btn.dataset.productEdit))));

                @if ($openProduct)
                    openProduct(@json($productEditData($openProduct)), @json($productEditHasOld ? $oldEdit : null));
                @endif
            }

            const typeEditEl = document.getElementById('typeEditModal');
            if (typeEditEl) {
                const form = typeEditEl.querySelector('form');
                const openType = (type, old) => {
                    if (!old) ModalForm.clearErrors(typeEditEl);
                    form.querySelector('[data-edit-id]').value = type.id;
                    form.action = type.url;
                    form.elements.name_product_type.value = (old || type).name_product_type || '';
                    ModalForm.open(typeEditEl);
                };

                document.querySelectorAll('[data-type-edit]').forEach((btn) =>
                    btn.addEventListener('click', () => openType(JSON.parse(btn.dataset.typeEdit))));

                @if ($openType)
                    openType(@json($typeEditData($openType)), @json($typeEditHasOld ? $oldEdit : null));
                @endif
            }

            document.querySelectorAll('#productCreateModal, #typeCreateModal').forEach(function(el) {
                el.addEventListener('shown.bs.modal', function() {
                    const first = el.querySelector('input[type="text"]');
                    if (first) first.focus();
                });
            });
        });
    </script>
@endsection
