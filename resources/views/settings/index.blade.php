@extends('layout')

@section('namepage')
    ตั้งค่า
@endsection

@section('content')
    <div class="row g-3">

        <div class="col-md-4">
            <a href="{{ route('settings.documents') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5>ตั้งค่าเอกสาร</h5>
                    </div>
                </div>
            </a>
        </div>

        {{-- เพิ่มใหม่ : จัดการผู้ใช้งาน --}}
        <div class="col-md-4">
            <a href="{{ route('users.index') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5>จัดการผู้ใช้งาน</h5>
                    </div>
                </div>
            </a>
        </div>

    </div>
@endsection