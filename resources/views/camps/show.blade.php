@extends('layout')

@section('namepage')
    <div class="container">
        <h3>{{ $camp->name_camp }}</h3>
    </div>
@endsection

@section('content')
    {{-- หน้าดูข้อมูลแคมป์ = read-only
         การแก้ไขทุกอย่าง (รวมถึงเพิ่ม/ถอนรถ) ทำที่หน้าแก้ไข --}}
    <div class="container py-2">
        @if (session('info'))
            <div class="alert alert-info shadow-sm">{{ session('info') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning shadow-sm">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <a href="{{ route('camps.index') }}" class="btn btn-sm btn-light mb-3">
            <i class="bi bi-arrow-left me-1"></i>
            กลับหน้ารายการ
        </a>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <div class="text-muted small mb-1">{{ $camp->code_camp }}</div>
                        <h5 class="mb-0">{{ $camp->name_camp }}</h5>
                    </div>

                    <div class="text-end">
                        <span class="badge bg-{{ $camp->status_color }} fs-6 mb-2">
                            {{ $camp->status_label }}
                        </span>
                        <br>
                        <a href="{{ route('camps.edit', $camp->id_camp) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil-square me-1"></i>
                            แก้ไขข้อมูล
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">ลูกค้า</div>
                        <div>{{ $camp->customer->name_customer ?? '-' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">ใบสั่งขายในแคมป์</div>
                        <div>
                            @forelse ($camp->salesOrders as $so)
                                <div>
                                    <a href="{{ route('sales_orders.show', $so) }}" class="text-decoration-none">
                                        {{ $so->code_so }}
                                    </a>
                                    <span class="text-muted">· {{ number_format($so->total_amount, 2) }} บาท</span>
                                    <span class="badge bg-{{ $so->status_color }}">{{ $so->status_label }}</span>
                                </div>
                            @empty
                                @if ($camp->quotation)
                                    {{-- แคมป์เก่าที่ผูกใบเสนอราคาตรง ๆ --}}
                                    <a href="{{ route('quotations.show', $camp->quotation) }}" class="text-decoration-none">
                                        {{ $camp->quotation->code_quot }}
                                    </a>
                                    <span class="text-muted">
                                        · {{ number_format($camp->quotation->total_amount, 2) }} บาท (ใบเสนอราคาเดิม)
                                    </span>
                                @else
                                    <span class="text-muted">ยังไม่มีใบสั่งขาย — เพิ่มได้จากหน้าใบสั่งขาย ปุ่ม "เพิ่มเข้าแคมป์เดิม"</span>
                                @endif
                            @endforelse
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">ที่ตั้ง</div>
                        <div>{{ $camp->full_address ?: 'ยังไม่ระบุที่อยู่' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">ผู้ติดต่อหน้างาน</div>
                        <div>
                            {{ $camp->contact_name ?: '-' }}
                            @if ($camp->contact_phone)
                                <span class="text-muted">· {{ $camp->contact_phone }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">พิกัด</div>
                        <div>
                            @if ($camp->latitude && $camp->longitude)
                                {{ $camp->latitude }}, {{ $camp->longitude }}
                            @else
                                <span class="text-danger">ยังไม่ปักหมุด (คำนวณระยะทางอัตโนมัติไม่ได้)</span>
                            @endif
                        </div>
                    </div>

                    @if ($camp->note)
                        <div class="col-12">
                            <div class="text-muted small">หมายเหตุ</div>
                            <div>{{ $camp->note }}</div>
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="text-muted small">
                            สร้างเมื่อ {{ $camp->created_at->format('d/m/Y') }}
                            @if ($camp->updated_at->ne($camp->created_at))
                                · แก้ไขล่าสุด {{ $camp->updated_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                </div>

                @if ($camp->latitude && $camp->longitude)
                    <div id="campShowMap" class="mt-3"
                        style="width:100%;height:320px;border-radius:10px;background:#e9ecef;"></div>

                    <div id="campMapError" class="alert alert-danger mt-2 d-none" role="alert"></div>

                    <div class="mt-2">
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $camp->latitude }},{{ $camp->longitude }}"
                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            เปิดใน Google Maps
                        </a>
                    </div>
                @endif

            </div>
        </div>

        {{-- ==================== ทำงานต่อจากแคมป์ ====================
             แคมป์ = แฟ้มงาน: เริ่มเอกสารทุกใบจากตรงนี้ได้ ไม่ต้องไปหาตามเมนู
             ปุ่มขึ้นตามสถานะแคมป์ + สิทธิ์ของผู้ใช้ --}}
        @php
            $user        = auth()->user();
            $next        = \App\Models\Camp::NEXT_STEP[$camp->status_camp] ?? null;
            $canEditCamp = $user->canEdit('camps');
            $closing     = $next && $next[0] === 'closed';
        @endphp

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small me-1">ทำงานต่อ:</span>

                @if ($camp->isWorking() && $user->canEdit('delivery_notes'))
                    <a href="{{ route('delivery_notes.create', ['camp' => $camp->id_camp]) }}" class="btn btn-sm btn-dark">
                        <i class="bi bi-truck me-1"></i>ออกใบส่งของ
                    </a>
                @endif

                @if ($pendingNotes->isNotEmpty() && $camp->isOpen() && $user->canEdit('invoices'))
                    <a href="{{ route('invoices.create', ['group' => 'camp-' . $camp->id_camp]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-receipt me-1"></i>ออกใบแจ้งหนี้
                        <span class="badge bg-light text-dark ms-1">รอออก {{ $pendingNotes->count() }} ใบ</span>
                    </a>
                @endif

                @if ($camp->isOpen() && $user->canEdit('receipts'))
                    <a href="{{ route('receipts.create', ['type' => 'advance', 'camp' => $camp->id_camp]) }}"
                       class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-cash-coin me-1"></i>รับเงินล่วงหน้า
                    </a>
                @endif

                @if ($camp->isOpen() && $user->canEdit('sales_orders'))
                    <a href="{{ route('sales_orders.create') }}" class="btn btn-sm btn-outline-secondary"
                       title="สร้างใบสั่งขาย ยืนยันแล้วกด &quot;เพิ่มเข้าแคมป์เดิม&quot; ในหน้าใบสั่งขาย">
                        <i class="bi bi-plus-lg me-1"></i>ใบสั่งขายเพิ่ม
                    </a>
                @endif

                {{-- เปลี่ยนสถานะทีละขั้น --}}
                @if ($next && $canEditCamp)
                    <form method="POST" action="{{ route('camps.status', $camp) }}" class="ms-auto"
                          data-confirm="{{ $closing
                              ? 'ปิดแคมป์ ' . $camp->code_camp . ' หลังปิดจะออกเอกสารเพิ่มไม่ได้ (เปิดกลับได้ภายหลัง)'
                              : 'เปลี่ยนสถานะแคมป์เป็น "' . \App\Models\Camp::STATUS_LABELS[$next[0]] . '"' }}"
                          data-confirm-title="ยืนยันการเปลี่ยนสถานะแคมป์"
                          data-confirm-variant="{{ $closing ? 'danger' : 'primary' }}"
                          data-confirm-ok="{{ $next[1] }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status_camp" value="{{ $next[0] }}">
                        <button type="submit" class="btn btn-sm {{ $closing ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                @disabled($closing && $closeBlockers)>
                            <i class="bi {{ $next[2] }} me-1"></i>{{ $next[1] }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- ยังปิดแคมป์ไม่ได้ → บอกว่าติดอะไร --}}
            @if ($closing && $closeBlockers)
                <div class="card-footer bg-white small">
                    <div class="text-muted mb-1"><i class="bi bi-info-circle me-1"></i>ก่อนปิดแคมป์ต้องจัดการ:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($closeBlockers as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- ==================== สรุปการเงินของแคมป์ ==================== --}}
        @php
            $tiles = [
                ['มูลค่าตามสัญญา',          $summary['contract_value'],   'text-dark',    'รวม VAT ของใบสั่งขายทุกใบ'],
                ['ส่งของแล้ว',              $summary['delivered_value'],  'text-dark',    'มูลค่าก่อน VAT'],
                ['ออกใบแจ้งหนี้แล้ว',        $summary['invoiced'],         'text-dark',    'รวม VAT'],
                ['รับเงินแล้วทั้งหมด',        $summary['cash_received'],    'text-success', 'ตามใบแจ้งหนี้ + ล่วงหน้า + ขายสด'],
                ['เงินล่วงหน้าคงเหลือ',      $summary['advance_balance'],  'text-warning', 'ยังไม่ได้หักในใบแจ้งหนี้'],
                ['ลูกค้าค้างชำระ',           $summary['outstanding'],      $summary['outstanding'] > 0 ? 'text-danger' : 'text-success', 'ใบแจ้งหนี้ที่ยังไม่ได้รับเงิน'],
            ];
        @endphp

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">สรุปการเงินของแคมป์</span>
            </div>

            <div class="card-body">
                <div class="row g-2 text-center">
                    @foreach ($tiles as [$label, $value, $color, $hint])
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="bg-light rounded-3 p-2 h-100">
                                <div class="small text-muted">{{ $label }}</div>
                                <div class="fw-bold {{ $color }}">{{ number_format($value, 2) }}</div>
                                <div class="text-muted" style="font-size: 11px;">{{ $hint }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ==================== ใบเสร็จของแคมป์ ==================== --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">ใบเสร็จของแคมป์นี้</div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>เลขที่</th>
                            <th>ประเภท</th>
                            <th>วันที่รับเงิน</th>
                            <th class="text-end">ยอดรับ</th>
                            <th class="text-end">ล่วงหน้าคงเหลือ</th>
                            <th class="text-center" style="width: 100px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($receipts as $rc)
                            <tr>
                                <td class="fw-semibold">{{ $rc->code_rc }}</td>
                                <td><span class="badge bg-{{ $rc->type_badge }}">{{ $rc->type_label }}</span></td>
                                <td>{{ thai_date($rc->date_receipt) }}</td>
                                <td class="text-end">{{ number_format($rc->total, 2) }}</td>
                                <td class="text-end">
                                    @if ($rc->isAdvance())
                                        {{ number_format($rc->advanceRemaining(), 2) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('receipts.show', $rc->id_receipt) }}"
                                       class="btn btn-sm btn-outline-primary">ดู</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ยังไม่มีใบเสร็จของแคมป์นี้</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ความคืบหน้าการส่งของ --}}
        @if ($progress->isNotEmpty())
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    ความคืบหน้าการส่งของ (เทียบกับยอดตามสัญญา)
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รายการ</th>
                                <th class="text-end">ตามสัญญา</th>
                                <th class="text-end">ส่งแล้ว</th>
                                <th class="text-end">คงเหลือ</th>
                                <th style="width: 200px;">ความคืบหน้า</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($progress as $row)
                                <tr>
                                    <td>{{ $row['name_product'] }}</td>
                                    <td class="text-end">{{ number_format($row['ordered'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['delivered'], 2) }}</td>
                                    <td class="text-end fw-semibold">
                                        @if ($row['over'] > 0)
                                            <span class="text-danger">
                                                เกิน {{ number_format($row['over'], 2) }}
                                            </span>
                                        @else
                                            {{ number_format($row['remaining'], 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar bg-{{ $row['over'] > 0 ? 'danger' : ($row['percent'] >= 100 ? 'success' : 'dark') }}"
                                                role="progressbar" style="width: {{ $row['percent'] }}%;">
                                                {{ $row['percent'] }}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ใบส่งของของแคมป์นี้ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">ใบส่งของของแคมป์นี้</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>เลขที่</th>
                            <th>วันที่ส่ง</th>
                            <th class="text-end">มูลค่า</th>
                            <th class="text-center">ใบแจ้งหนี้</th>
                            <th class="text-center" style="width: 100px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deliveryNotes as $note)
                            <tr>
                                <td class="fw-semibold">{{ $note->code_delivery }}</td>
                                <td>{{ $note->delivery_date->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    {{ number_format($note->details->sum('total_price'), 2) }}
                                </td>
                                <td class="text-center">
                                    @if ($note->invoice)
                                        <a href="{{ route('invoices.show', $note->invoice) }}"
                                           class="badge bg-success text-decoration-none">{{ $note->invoice->code_inv }}</a>
                                    @else
                                        <span class="badge bg-secondary">รอออก</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('delivery_notes.show', $note) }}"
                                        class="btn btn-sm btn-outline-primary">ดู</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    ยังไม่มีใบส่งของจากแคมป์นี้
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">รถบรรทุกและคนขับที่ทำงานที่แคมป์นี้</span>
                <a href="{{ route('camps.edit', $camp->id_camp) }}#camp-trucks"
                    class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>จัดการรถ/คนขับ (หน้าแก้ไข)
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ทะเบียน</th>
                            <th>ยี่ห้อ / รุ่น</th>
                            <th>คนขับ</th>
                            <th>วันที่เริ่ม</th>
                            <th>หมายเหตุ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($camp->trucks as $truck)
                            <tr>
                                <td class="fw-semibold">{{ $truck->id_truck }}</td>
                                <td>{{ $truck->brand->name_brand ?? '-' }} {{ $truck->model->name_model ?? '' }}</td>
                                <td>
                                    @if ($truck->pivot->driver)
                                        <a href="{{ route('drivers.show', $truck->pivot->driver) }}" class="text-decoration-none">
                                            {{ $truck->pivot->driver->full_name }}
                                        </a>
                                        @if ($truck->pivot->driver->phone_driver)
                                            <div class="small text-muted">{{ $truck->pivot->driver->phone_driver }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">ยังไม่ระบุ</span>
                                    @endif
                                </td>
                                <td>{{ thai_date($truck->pivot->assigned_date) }}</td>
                                <td class="small text-muted">{{ $truck->pivot->note ?? '-' }}</td>
                                <td>
                                    @if ($truck->pivot->released_date)
                                        <span class="badge bg-secondary">
                                            ถอนแล้ว
                                            {{ thai_date($truck->pivot->released_date) }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">กำลังทำงาน</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ยังไม่มีรถประจำแคมป์นี้</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($camp->latitude && $camp->longitude)
        <script>
            (() => {
                const campPosition = {
                    lat: {{ $camp->latitude }},
                    lng: {{ $camp->longitude }}
                };

                const campName = @json($camp->name_camp);
                const campAddress = @json($camp->full_address ?: '');
                const errorElement = document.getElementById('campMapError');

                window.initCampShowMap = function() {
                    const map = new google.maps.Map(document.getElementById('campShowMap'), {
                        center: campPosition,
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                        gestureHandling: 'cooperative'
                    });

                    const marker = new google.maps.Marker({
                        map,
                        position: campPosition,
                        title: campName
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: '<div style="font-weight:600;margin-bottom:2px;">' + campName + '</div>' +
                            (campAddress ?
                                '<div style="font-size:12px;color:#666;">' + campAddress + '</div>' :
                                '')
                    });

                    infoWindow.open(map, marker);

                    marker.addListener('click', () => infoWindow.open(map, marker));
                };

                window.gm_authFailure = function() {
                    errorElement.textContent =
                        'Google Maps โหลดไม่ได้ กรุณาตรวจสอบ API Key, Billing และ Website restrictions';
                    errorElement.classList.remove('d-none');
                };
            })();
        </script>

        @if (config('services.google_maps.key'))
            <script async
                src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initCampShowMap&loading=async&language=th&region=TH">
            </script>
        @else
            <script>
                document.getElementById('campMapError').textContent =
                    'ยังไม่ได้ตั้งค่า GOOGLE_MAPS_API_KEY ในไฟล์ .env';
                document.getElementById('campMapError').classList.remove('d-none');
            </script>
        @endif
    @endif
@endsection
