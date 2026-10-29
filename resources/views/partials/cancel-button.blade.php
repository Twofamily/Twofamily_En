{{--
    ปุ่ม "ยกเลิกเอกสาร" ในหน้าดู — กดแล้วขึ้นกล่องยืนยันพร้อมช่องกรอกเหตุผล (confirm.js)
    @include('partials.cancel-button', [
        'action'  => route('invoices.destroy', $invoice->id_invoice),
        'label'   => 'ยกเลิกใบแจ้งหนี้',
        'message' => 'ข้อความในกล่องยืนยัน',
        'blocker' => null | 'เหตุผลที่ยกเลิกไม่ได้',   // มีค่า = แสดงปุ่มสีเทาพร้อมเหตุผล
    ])
--}}
@if ($blocker ?? null)
    <span class="btn btn-sm btn-outline-secondary disabled" title="{{ $blocker }}" aria-disabled="true">
        <i class="bi bi-x-octagon me-1"></i>{{ $label }}
    </span>
@else
    <form method="POST" action="{{ $action }}" class="d-inline"
          data-confirm="{{ $message }} — ยกเลิกแล้วเอกสารยังอยู่ในระบบ (เลขที่ไม่หาย) แต่จะไม่นับในยอดใด ๆ และย้อนกลับไม่ได้"
          data-confirm-title="{{ $label }}"
          data-confirm-variant="danger"
          data-confirm-ok="{{ $label }}"
          data-confirm-reason="cancel_reason">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" type="submit">
            <i class="bi bi-x-octagon me-1"></i>{{ $label }}
        </button>
    </form>
@endif
