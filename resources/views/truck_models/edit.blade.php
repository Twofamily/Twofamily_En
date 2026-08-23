@extends('layout')

@section('namepage')
    <div class="container">
        <h3>เพิ่มรุ่นรถ</h3>
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

                <form method="POST" action="{{ route('truck_models.update', $model) }}" autocomplete="off">
            @csrf @method('PUT')
            @include('truck_models._form', ['model' => $model, 'brands' => $brands])

            <div class="mt-4">
                <button class="btn btn-dark">บันทึก</button>
                <a href="{{ route('truck_models.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
            </div>
        </form>
    </div>
@endsection