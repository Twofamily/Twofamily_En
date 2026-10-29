@extends('layout')

@section('namepage')
    <div class="container">
        <h3>ข้อมูลลูกค้า</h3>
    </div>
@endsection

@section('content')
    <div class="container py-4">

        <div class="card shadow-sm" style="max-width:860px;margin:auto">
            <div class="card-body p-4">

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;">
                        <i class="bi {{ $customer->isCompany() ? 'bi-building' : 'bi-person' }} fs-4 text-secondary"></i>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ $customer->name_customer }}</h4>
                        <span class="badge text-bg-light border">{{ $customer->type_label }}</span>
                        @if ($customer->branchLabel())
                            <span class="badge text-bg-light border">{{ $customer->branchLabel() }}</span>
                        @endif
                    </div>
                </div>

                <dl class="row mb-0">
                    @unless ($customer->isCompany())
                        <dt class="col-sm-4 text-muted fw-normal">คำนำหน้า / ชื่อ / นามสกุล</dt>
                        <dd class="col-sm-8">
                            {{ $customer->name_prefix ?: '-' }} /
                            {{ $customer->first_name ?: '-' }} /
                            {{ $customer->last_name ?: '-' }}
                        </dd>
                    @else
                        <dt class="col-sm-4 text-muted fw-normal">ผู้ติดต่อ</dt>
                        <dd class="col-sm-8">{{ $customer->contact_name ?: '-' }}</dd>
                    @endunless

                    <dt class="col-sm-4 text-muted fw-normal">เลขประจำตัวผู้เสียภาษี</dt>
                    <dd class="col-sm-8">{{ $customer->formattedTaxId() ?: '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">เบอร์โทร</dt>
                    <dd class="col-sm-8">{{ $customer->phone_customer ?: '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">อีเมล</dt>
                    <dd class="col-sm-8">{{ $customer->email_customer ?: '-' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">ที่อยู่</dt>
                    <dd class="col-sm-8">{{ customer_address($customer) }}</dd>
                </dl>

                <div class="d-flex gap-2 pt-4">
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                        ย้อนกลับ
                    </a>

                    @if (auth()->user()->canEdit() && ! $customer->isWalkIn())
                        <a href="{{ route('customers.index', ['edit' => $customer->id_customer]) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i>แก้ไข
                        </a>
                    @endif
                </div>

            </div>
        </div>

    </div>
@endsection
