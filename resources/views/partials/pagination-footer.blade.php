{{--
    สรุปจำนวนรายการ + ปุ่มเปลี่ยนหน้า — ใช้ร่วมกันทุกหน้ารายการ

    ใช้:  @include('partials.pagination-footer', ['paginator' => $quotations])
    (ต้องเรียก ->withQueryString() ที่ Controller แล้ว)
--}}
@if ($paginator->total() > 0)
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">

        <div class="text-muted small">
            แสดง {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}
            จากทั้งหมด {{ number_format($paginator->total()) }} รายการ
            @if ($paginator->lastPage() > 1)
                (หน้า {{ $paginator->currentPage() }} จาก {{ $paginator->lastPage() }})
            @endif
        </div>

        <div>
            {{ $paginator->links() }}
        </div>
    </div>
@endif