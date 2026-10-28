@extends('layout')

@section('namepage')
    <div class="container">
        <h3>แก้ไขรถบรรทุก {{ $truck->id_truck }}</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('trucks.update', $truck->id_truck) }}" enctype="multipart/form-data" autocomplete="off"
            data-confirm="ต้องการบันทึกการแก้ไขข้อมูลรถบรรทุกคันนี้หรือไม่" data-confirm-title="ยืนยันการแก้ไขข้อมูล" data-confirm-ok="บันทึกการแก้ไข" data-confirm-variant="primary">
            @csrf 
            @method('PUT')

            @include('trucks._form', [
                'truck' => $truck,
                'mode' => 'edit',
                'brands' => $brands,
                'provinces' => config('provinces'),
            ])

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">บันทึกการแก้ไข</button>
                <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary ms-1">ย้อนกลับ</a>
            </div>

        </form>
    </div>
@endsection