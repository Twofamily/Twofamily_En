@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขยี่ห้อรถ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">
        <form method="POST" action="{{ route('truck_brands.update', $brand) }}" autocomplete="off">
            @csrf @method('PUT')
            @include('truck_brands._form', ['brand' => $brand])

            <button class="btn btn-dark">บันทึก</button>
            <a href="{{ route('truck_brands.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
        </form>
    </div>
@endsection