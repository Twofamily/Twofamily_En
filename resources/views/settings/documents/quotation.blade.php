@extends('layout')

@section('namepage')
    ตั้งค่าใบเสนอราคา
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">


        <form method="POST" action="{{ route('settings.quotation.update') }}">
            @csrf

            <div class="row g-3">

                <div class="col-12">
                    <h5>ข้อมูลผู้ออก</h5>
                </div>

                <div class="col-md-6">
                    <label>ชื่อบริษัท (ผู้ออกเอกสาร)</label>
                    <input type="text" class="form-control" name="company_name"
                        value="{{ $settings['company_name'] ?? '' }}">
                </div>

                <div class="col-md-12">
                    <label>ที่อยู่บริษัท</label>
                    <textarea class="form-control" rows="2" name="company_address">{{ $settings['company_address'] ?? '' }}</textarea>
                </div>

                <div class="col-md-6">
                    <label>เบอร์โทร</label>
                    <input type="text" class="form-control" name="company_phone"
                        value="{{ $settings['company_phone'] ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label>เลขผู้เสียภาษี</label>
                    <input type="text" class="form-control" name="tax_id"
                        value="{{ $settings['tax_id'] ?? '' }}">
                </div>

                <hr>

                <div class="col-12">
                    <h5>ข้อมูลใบเสนอราคา</h5>
                </div>

                <div class="col-md-4">
                    <label>เครดิต (วัน)</label>
                    <input type="number" class="form-control" name="quotation_credit_term"
                        value="{{ $settings['quotation_credit_term'] ?? 14 }}">
                </div>

                <div class="col-12">
                    <label>หมายเหตุ</label>
                    <textarea class="form-control" rows="3" name="quotation_note">{{ $settings['quotation_note'] ?? '' }}</textarea>
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('settings.documents') }}" class="btn btn-secondary">
                    กลับ
                </a>

                <button type="submit" class="btn btn-primary">
                    บันทึก
                </button>
            </div>

        </form>

    </div>
</div>
@endsection