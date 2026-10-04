@extends('layout')

@section('namepage')
    ตั้งค่าระบบ
@endsection

@section('content')

    {{-- แท็บ: แต่ละแท็บเป็นหน้าแยก (URL ของตัวเอง) และบันทึกแยกกัน --}}
    <ul class="nav nav-tabs mb-4">
        @foreach ($groups as $key => $info)
            <li class="nav-item">
                <a href="{{ route('settings.index', $key) }}"
                    class="nav-link {{ $key === $group ? 'active fw-semibold' : 'text-secondary' }}">
                    <i class="bi {{ $info['icon'] }} me-2"></i>{{ $info['label'] }}
                </a>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('settings.update', $group) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include("settings.groups.{$group}")

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-dark">
                <i class="bi bi-save me-2"></i>บันทึก
            </button>
            <a href="{{ route('settings.index', $group) }}" class="btn btn-outline-secondary">ยกเลิก</a>
        </div>
    </form>

@endsection