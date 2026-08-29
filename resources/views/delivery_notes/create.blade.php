@extends('layout')

@section('namepage')
    <div class="container">
        <h3>สร้างใบส่งของ</h3>
    </div>
@endsection

@section('content')
<div class="container py-3">

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ขั้นที่ 1 เลือกแคมป์ --}}
    <form method="GET" action="{{ route('delivery-notes.create') }}" class="mb-4">
        <label for="camp_picker" class="form-label">เลือกแคมป์</label>

        <div class="d-flex gap-2">
            <select id="camp_picker" name="camp" class="form-select" onchange="this.form.submit()">
                <option value="">— เลือกแคมป์ —</option>
                @foreach ($camps as $c)
                    <option value="{{ $c->id_camp }}" @selected($camp?->id_camp == $c->id_camp)>
                        {{ $c->code_camp }} — {{ $c->name_camp }}
                        ({{ $c->customer->name_customer ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-text">แสดงเฉพาะแคมป์ที่เปิดใช้งานอยู่</div>
    </form>

    @if ($camp)
        {{-- ข้อมูลที่ดึงมาอัตโนมัติ --}}
        <div class="border rounded-3 p-3 mb-4 bg-light">
            <div class="row g-2 small">
                <div class="col-md-4"><strong>ลูกค้า:</strong> {{ $camp->customer->name_customer ?? '-' }}</div>
                <div class="col-md-4">
                    <strong>ใบเสนอราคา:</strong>
                    {{ $camp->quotation ? $camp->quotation->code_quot : 'ไม่ได้อ้างอิง' }}
                </div>
                <div class="col-md-4"><strong>สถานที่ส่ง:</strong> {{ $camp->full_address ?: '-' }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('delivery-notes.store') }}">
            @csrf
            <input type="hidden" name="id_camp" value="{{ $camp->id_camp }}">

            <div class="mb-3 col-md-4">
                <label for="delivery_date" class="form-label">วันที่ส่งของ</label>
                <input type="date" id="delivery_date" name="delivery_date"
                       value="{{ old('delivery_date', now()->format('Y-m-d')) }}"
                       class="form-control @error('delivery_date') is-invalid @enderror" required>
                @error('delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if ($suggestedItems->isEmpty())
                <div class="alert alert-warning">
                    แคมป์นี้ไม่ได้อ้างอิงใบเสนอราคา จึงไม่มีรายการให้เติมอัตโนมัติ
                    กรุณาผูกใบเสนอราคาที่หน้าแก้ไขแคมป์ก่อน
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>สินค้า</th>
                                <th class="text-end">ตามสัญญา</th>
                                <th class="text-end">ส่งแล้ว</th>
                                <th class="text-end">คงเหลือ</th>
                                <th style="width:140px">จำนวนที่ส่ง</th>
                                <th style="width:140px">ราคา/หน่วย</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suggestedItems as $i => $item)
                                <tr @class(['table-secondary' => $item['remaining'] <= 0])>
                                    <td>
                                        {{ $item['name_product'] }}
                                        <input type="hidden" name="items[{{ $i }}][id_product]"
                                               value="{{ $item['id_product'] }}">
                                    </td>
                                    <td class="text-end">{{ number_format($item['ordered'], 2) }}</td>
                                    <td class="text-end">{{ number_format($item['delivered'], 2) }}</td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($item['remaining'], 2) }}
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                               name="items[{{ $i }}][quantity]"
                                               value="{{ old("items.$i.quantity", $item['remaining']) }}"
                                               class="form-control form-control-sm text-end">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                               name="items[{{ $i }}][price]"
                                               value="{{ old("items.$i.price", $item['price_per_unit']) }}"
                                               class="form-control form-control-sm text-end">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('items.*.quantity')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-dark">บันทึกใบส่งของ</button>
                    <a href="{{ route('delivery-notes.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                </div>
            @endif
        </form>
    @endif

</div>
@endsection