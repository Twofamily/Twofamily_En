@php
    $crumbs = \App\Support\Breadcrumb::build();

    // หน้าไหนอยากเปลี่ยนข้อความตัวสุดท้าย ให้ใส่ @section('breadcrumb_current', '...')
    if (count($crumbs) && $__env->hasSection('breadcrumb_current')) {
        $crumbs[array_key_last($crumbs)]['label'] = trim($__env->yieldContent('breadcrumb_current'));
    }
@endphp

@if (count($crumbs) > 1)
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            @foreach ($crumbs as $crumb)
                @if ($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">
                        @if ($crumb['icon'])<i class="bi {{ $crumb['icon'] }} me-1"></i>@endif{{ $crumb['label'] }}
                    </li>
                @elseif ($crumb['url'])
                    <li class="breadcrumb-item">
                        <a href="{{ $crumb['url'] }}" class="text-decoration-none">
                            @if ($crumb['icon'])<i class="bi {{ $crumb['icon'] }} me-1"></i>@endif{{ $crumb['label'] }}
                        </a>
                    </li>
                @else
                    <li class="breadcrumb-item">{{ $crumb['label'] }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif