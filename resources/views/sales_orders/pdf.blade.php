<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>ใบสั่งขาย {{ $salesOrder->code_so }}</title>

    <style>
        @font-face {
            font-family: 'sarabun';
            font-weight: normal;
            src: url("{{ public_path('fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }

        {{-- dompdf ทำตัวหนาเองไม่ได้ ต้องมีไฟล์ Bold แยก ถ้าไม่มีไฟล์จะใช้ตัวปกติแทน --}}
        @if (file_exists(public_path('fonts/Sarabun-Bold.ttf')))
        @font-face {
            font-family: 'sarabun';
            font-weight: bold;
            src: url("{{ public_path('fonts/Sarabun-Bold.ttf') }}") format('truetype');
        }
        @endif

        @page {
            margin: 28px 36px 40px 36px;
        }

        body {
            font-family: 'sarabun';
            font-size: 13px;
            color: #222;
            line-height: 1.35;
        }

        table {
            border-collapse: collapse;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .muted       { color: #666; }
        .small       { font-size: 11.5px; }

        /* ---------- หัวเอกสาร ---------- */
        .company-name {
            font-size: 19px;
            font-weight: bold;
            color: #4a3526;
        }

        .doc-box {
            border: 1.5px solid #4a3526;
            width: 100%;
        }

        .doc-box .doc-title {
            background-color: #4a3526;
            color: #fff;
            text-align: center;
            padding: 6px 0 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .doc-box .doc-title span {
            font-size: 12px;
            font-weight: normal;
        }

        .doc-box td {
            padding: 3px 8px;
        }

        .rule {
            border-bottom: 2px solid #4a3526;
            margin: 10px 0 12px 0;
        }

        /* ---------- ข้อมูลลูกค้า / อ้างอิง ---------- */
        .info {
            width: 100%;
            border: 1px solid #bfb3a8;
        }

        .info td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .info .label {
            color: #666;
        }

        .info .head {
            background-color: #efe9e3;
            font-weight: bold;
            border-bottom: 1px solid #bfb3a8;
        }

        .info .divider {
            border-left: 1px solid #bfb3a8;
        }

        /* ---------- ตารางรายการ ---------- */
        .items {
            width: 100%;
            margin-top: 12px;
        }

        .items th {
            background-color: #4a3526;
            color: #fff;
            font-weight: bold;
            padding: 6px 5px;
            border: 1px solid #4a3526;
        }

        .items td {
            padding: 5px;
            border-left: 1px solid #bfb3a8;
            border-right: 1px solid #bfb3a8;
        }

        .items tbody tr:last-child td {
            border-bottom: 1px solid #bfb3a8;
        }

        .items .row-alt td {
            background-color: #faf7f4;
        }

        /* ---------- สรุปยอด ---------- */
        .summary {
            width: 100%;
        }

        .summary td {
            padding: 4px 8px;
            border-bottom: 1px solid #e3dbd3;
        }

        .summary .grand td {
            background-color: #4a3526;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            border-bottom: none;
        }

        .baht-text {
            background-color: #efe9e3;
            border: 1px solid #bfb3a8;
            padding: 5px 8px;
            text-align: center;
            font-weight: bold;
        }

        .note {
            border: 1px solid #bfb3a8;
            padding: 6px 8px;
            min-height: 70px;
        }

        /* ---------- ท้ายหน้า ---------- */
        .footer {
            position: fixed;
            bottom: -24px;
            left: 0;
            right: 0;
            font-size: 10.5px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 3px;
        }
    </style>
</head>

<body>

    <div class="footer">
        <table width="100%">
            <tr>
                <td>{{ setting('company_name') }}</td>
                <td class="text-right">ใบสั่งขายเลขที่ {{ $salesOrder->code_so }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== หัวเอกสาร: ข้อมูลบริษัท (ซ้าย) / กล่องเลขที่เอกสาร (ขวา) ===== --}}
    <table width="100%">
        <tr>
            <td width="62%" style="vertical-align: top;">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" style="height: 60px; margin-bottom: 4px;"><br>
                @endif
                <div class="company-name">{{ setting('company_name') }}</div>
                <div class="small">
                    {{ setting('company_address') }}<br>
                    โทร {{ setting('company_phone') }}<br>
                    เลขประจำตัวผู้เสียภาษี {{ setting('tax_id') ?? '-' }}
                </div>
            </td>

            <td width="38%" style="vertical-align: top;">
                <table class="doc-box small">
                    <tr>
                        <td colspan="2" class="doc-title">
                            ใบสั่งขาย<br><span>SALES ORDER</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-top: 6px;">เลขที่</td>
                        <td class="text-right" style="padding-top: 6px;"><b>{{ $salesOrder->code_so }}</b></td>
                    </tr>
                    <tr>
                        <td class="muted">วันที่สั่งซื้อ</td>
                        <td class="text-right">{{ $salesOrder->order_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-bottom: 6px;">กำหนดส่งมอบ</td>
                        <td class="text-right" style="padding-bottom: 6px;">
                            {{ $salesOrder->due_date?->format('d/m/Y') ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ===== ข้อมูลลูกค้า (ซ้าย) / เอกสารอ้างอิง (ขวา) ===== --}}
    <table class="info small">
        <tr>
            <td colspan="2" class="head" width="62%">ลูกค้า</td>
            <td colspan="2" class="head divider" width="38%">เอกสารอ้างอิง</td>
        </tr>
        <tr>
            <td class="label" width="12%">ชื่อ</td>
            <td width="50%"><b>{{ $salesOrder->customer->name_customer ?? '-' }}</b></td>
            <td class="label divider" width="16%">ใบเสนอราคา</td>
            <td width="22%">{{ $salesOrder->quotation->code_quot ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">ที่อยู่</td>
            <td>
                {{ $salesOrder->customer ? customer_address($salesOrder->customer) : '-' }}
            </td>
            <td class="label divider">เลขที่ PO</td>
            <td>{{ $salesOrder->po_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">เลขภาษี</td>
            <td>{{ $salesOrder->customer->tax_id ?? '-' }}</td>
            <td class="divider"></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">โทร</td>
            <td>{{ $salesOrder->customer->phone_customer ?? '-' }}</td>
            <td class="divider"></td>
            <td></td>
        </tr>
    </table>

    {{-- ===== ตารางรายการ ===== --}}
    <table class="items small">
        <thead>
            <tr>
                <th width="6%">ลำดับ</th>
                <th width="44%">รายการ</th>
                <th width="10%">จำนวน</th>
                <th width="10%">หน่วย</th>
                <th width="15%">ราคา/หน่วย</th>
                <th width="15%">จำนวนเงิน</th>
            </tr>
        </thead>

        <tbody>
            @php $i = 1; @endphp

            @foreach ($salesOrder->details as $detail)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $detail->product->name_product ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                    <td class="text-center">คิว</td>
                    <td class="text-right">{{ number_format($detail->price_per_unit, 2) }}</td>
                    {{-- อ่านจาก total_price ที่บันทึกไว้ ไม่คูณใหม่ตอนพิมพ์ --}}
                    <td class="text-right">{{ number_format($detail->total_price, 2) }}</td>
                </tr>
            @endforeach

            {{-- เติมแถวว่างให้ตารางเต็มหน้า --}}
            @for ($i; $i <= 8; $i++)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{--
        ===== ส่วนท้าย: สรุปยอด + ลายเซ็น =====
        ห้ามตัดหน้าตรงกลาง ถ้าที่ไม่พอ ทั้งก้อนจะย้ายไปหน้าถัดไปพร้อมกัน
        ลายเซ็นจะไม่หลุดไปอยู่หน้าเดียวโดด ๆ
    --}}
    <div style="page-break-inside: avoid;">

    {{-- ===== หมายเหตุ (ซ้าย) / สรุปยอด (ขวา) ===== --}}
    <table width="100%" class="small" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 12px;">
                <div class="note">
                    <b>หมายเหตุ</b><br>
                    {!! $salesOrder->note ? nl2br(e($salesOrder->note)) : '-' !!}
                </div>
            </td>

            <td width="45%" style="vertical-align: top;">
                {{--
                    ยอดเงินทุกบรรทัดอ่านจากคอลัมน์ที่บันทึกไว้ตอนออกเอกสาร
                    ไม่คำนวณสดตอนพิมพ์ เพื่อให้เอกสารเก่าพิมพ์ซ้ำได้เหมือนเดิม
                --}}
                <table class="summary">
                    <tr>
                        <td>รวมเป็นเงิน</td>
                        <td class="text-right">{{ number_format($salesOrder->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>หักส่วนลด</td>
                        <td class="text-right">{{ number_format($salesOrder->discount, 2) }}</td>
                    </tr>
                    {{-- แสดงเฉพาะเมื่อตารางมีคอลัมน์ after_discount --}}
                    @isset($salesOrder->after_discount)
                        <tr>
                            <td>ยอดหลังหักส่วนลด</td>
                            <td class="text-right">{{ number_format($salesOrder->after_discount, 2) }}</td>
                        </tr>
                    @endisset
                    <tr>
                        {{-- ใช้ vat_rate ที่บันทึกไว้ถ้ามีคอลัมน์ ไม่มีก็แสดง 7 ตามเดิม --}}
                        <td>ภาษีมูลค่าเพิ่ม {{ rtrim(rtrim(number_format($salesOrder->vat_rate ?? 7, 2), '0'), '.') }}%</td>
                        <td class="text-right">{{ number_format($salesOrder->vat_amount, 2) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>จำนวนเงินสุทธิ (บาท)</td>
                        <td class="text-right">{{ number_format($salesOrder->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (function_exists('baht_text'))
        <div class="baht-text small" style="margin-top: 8px;">
            ({{ baht_text($salesOrder->total_amount) }})
        </div>
    @endif

    @include('pdf.partials.signatures', ['slots' => [
        [
            'party' => 'customer',
            'label' => 'ผู้สั่งซื้อ',
        ],
        [
            'party' => 'company',
            'label' => 'ผู้รับคำสั่งซื้อ',
        ],
    ]])

    </div>{{-- /keep-together --}}

</body>

</html>