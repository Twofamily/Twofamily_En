{{-- เมนูลิงก์เพิ่มเติมใน <x-row-actions> เช่น พิมพ์ PDF --}}
@props([
    'href',
    'icon'    => 'bi-box-arrow-up-right',
    'variant' => null,   // success | warning | danger → สีตัวหนังสือ
])

<li>
    <a href="{{ $href }}" {{ $attributes->class(['dropdown-item', "text-{$variant}" => $variant]) }}>
        <i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $slot }}
    </a>
</li>
