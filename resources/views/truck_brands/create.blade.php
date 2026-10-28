@extends('layout')

@section('namepage')
    <div class="container">
        <h3>เพิ่มยี่ห้อรถ</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">
        <form method="POST" action="{{ route('truck_brands.store') }}" autocomplete="off"
            data-confirm="ต้องการบันทึกยี่ห้อรถบรรทุกนี้หรือไม่" data-confirm-title="ยืนยันการบันทึกข้อมูล" data-confirm-ok="บันทึก" data-confirm-variant="primary">
            @csrf
            @include('truck_brands._form', ['brand' => $brand])

            <button class="btn btn-dark">บันทึก</button>
            <a href="{{ route('truck_brands.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
        </form>
    </div>
@endsection