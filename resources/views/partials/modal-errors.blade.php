{{-- รายการข้อผิดพลาดในหน้าต่าง (modal) — @include('partials.modal-errors', ['bag' => $errors->createDriver]) --}}
@if ($bag->any())
    <div class="alert alert-danger py-2" data-modal-errors>
        <ul class="mb-0 small">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
