@extends('layout')

@section('namepage')
    ตั้งค่าใบแจ้งหนี้
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">


        <form method="POST" action="{{ route('settings.invoice.update') }}">
            @csrf

            <div class="row g-3">

                <div class="col-12">
                    <h5>ข้อมูลผู้ออก</h5>
                </div>

                <div class="col-md-6">
                    <label>ชื่อบริษัท</label>
                    <input type="text" class="form-control" name="company_name"
                        value="{{ $settings['company_name'] ?? '' }}">
                </div>

                <div class="col-md-12">
                    <label>ที่อยู่</label>
                    <textarea class="form-control" name="company_address">{{ $settings['company_address'] ?? '' }}</textarea>
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
                    <h5>ข้อมูลใบแจ้งหนี้</h5>
                </div>

                <div class="col-md-4">
                    <label>เครดิต (วัน)</label>
                    <input type="number" class="form-control" name="credit_term"
                        value="{{ $settings['credit_term'] ?? 7 }}">
                </div>

                <div class="col-12">
                    <label>หมายเหตุ</label>
                    <textarea class="form-control" name="invoice_note">{{ $settings['invoice_note'] ?? '' }}</textarea>
                </div>

                <hr>

                <div class="col-12">
                    <h5>บัญชีธนาคาร</h5>
                </div>

                <div class="col-md-6">
                    <label>ธนาคาร</label>
                    <input type="text" class="form-control" name="bank_name"
                        value="{{ $settings['bank_name'] ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label>ชื่อบัญชี</label>
                    <input type="text" class="form-control" name="bank_account_name"
                        value="{{ $settings['bank_account_name'] ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label>เลขบัญชี</label>
                    <input type="text" class="form-control" name="bank_account"
                        value="{{ $settings['bank_account'] ?? '' }}">
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('settings.documents') }}" class="btn btn-secondary">
                    กลับ
                </a>

                <button class="btn btn-primary">
                    บันทึก
                </button>
            </div>

        </form>

    </div>
</div>
@endsection