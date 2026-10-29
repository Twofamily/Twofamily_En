{{--
    เมนูที่ต้องส่งฟอร์มใน <x-row-actions> เช่น อนุมัติ / ยกเลิก / ระงับบัญชี
    ใส่ confirm="..." แล้วจะขึ้นกล่องยืนยัน (confirm.js) ก่อนส่ง
--}}
@props([
    'action',
    'method'         => 'POST',
    'icon'           => 'bi-check-lg',
    'variant'        => null,
    'confirm'        => null,
    'confirmTitle'   => 'ยืนยันการทำรายการ',
    'confirmVariant' => null,
    'confirmOk'      => 'ยืนยัน',
])

<li>
    <form method="POST" action="{{ $action }}" class="m-0"
          @if ($confirm)
              data-confirm="{{ $confirm }}"
              data-confirm-title="{{ $confirmTitle }}"
              data-confirm-variant="{{ $confirmVariant ?? $variant ?? 'primary' }}"
              data-confirm-ok="{{ $confirmOk }}"
          @endif>
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <button type="submit" {{ $attributes->class(['dropdown-item', "text-{$variant}" => $variant]) }}>
            <i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $slot }}
        </button>
    </form>
</li>
