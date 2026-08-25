@extends('layout')

@section('namepage')
    <div class="container">
        <h3>เพิ่มรถบรรทุก</h3>
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

        <form method="POST" action="{{ route('trucks.store') }}" enctype="multipart/form-data" autocomplete="off">
            @csrf

            @include('trucks._form', [
                'truck' => $truck,
                'mode' => 'create',
                'brands' => $brands,
                'provinces' => config('provinces'),
            ])

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">บันทึก</button>
                <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary">ย้อนกลับ</a>
            </div>
        </form>

    </div>
@endsection