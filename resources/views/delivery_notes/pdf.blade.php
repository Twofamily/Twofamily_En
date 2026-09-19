<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">

    @php
        // เลขที่อ่านจากคอลัมน์ code_dn (สร้างอัตโนมัติตอนบันทึก รูปแบบ DN-2569-0001)
        $dnCode = $deliveryNote->code_dn ?? '-';
        $qtCode = $deliveryNote->quotation?->code_quot ?? '-';
    @endphp

    <title>ใบส่งของ {{ $dnCode }}</title>

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

        .items .row-alt td {
            background-color: #faf7f4;
        }

        .items .total td {
            border-top: 1px solid #bfb3a8;
            border-bottom: 1px solid #bfb3a8;
            background-color: #efe9e3;
            font-weight: bold;
        }

        .receipt-note {
            border: 1px solid #bfb3a8;
            padding: 6px 8px;
            margin-top: 12px;
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
                <td class="text-right">ใบส่งของเลขที่ {{ $dnCode }}</td>
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
                            ใบส่งของ<br><span>DELIVERY NOTE</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-top: 6px;">เลขที่</td>
                        <td class="text-right" style="padding-top: 6px;"><b>{{ $dnCode }}</b></td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-bottom: 6px;">วันที่ส่ง</td>
                        <td class="text-right" style="padding-bottom: 6px;">
                            {{ $deliveryNote->delivery_date?->format('d/m/Y') ?? '-' }}
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
            <td colspan="2" class="head" width="62%">ส่งถึง (ลูกค้า)</td>
            <td colspan="2" class="head divider" width="38%">เอกสารอ้างอิง</td>
        </tr>
        <tr>
            <td class="label" width="12%">ชื่อ</td>
            <td width="50%"><b>{{ $deliveryNote->customer->name_customer ?? '-' }}</b></td>
            <td class="label divider" width="16%">ใบเสนอราคา</td>
            <td width="22%">
                {{ $qtCode }}
            </td>
        </tr>
        <tr>
            <td class="label">ที่อยู่</td>
            <td>
                {{ $deliveryNote->customer ? customer_address($deliveryNote->customer) : '-' }}
            </td>
            <td class="divider"></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">โทร</td>
            <td>{{ $deliveryNote->customer->phone_customer ?? '-' }}</td>
            <td class="divider"></td>
            <td></td>
        </tr>
    </table>

    {{-- ===== ตารางรายการ (ใบส่งของไม่แสดงราคา) ===== --}}
    <table class="items small">
        <thead>
            <tr>
                <th width="7%">ลำดับ</th>
                <th width="48%">รายการ</th>
                <th width="13%">จำนวน</th>
                <th width="10%">หน่วย</th>
                <th width="22%">หมายเหตุ</th>
            </tr>
        </thead>

        <tbody>
            @php $i = 1; @endphp

            @foreach ($deliveryNote->details as $detail)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $detail->product->name_product ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                    <td class="text-center">คิว</td>
                    <td></td>
                </tr>
            @endforeach

            {{-- เติมแถวว่างให้ตารางเต็มหน้า และเผื่อเขียนมือหน้างาน --}}
            @for ($i; $i <= 8; $i++)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor

            {{-- รวมปริมาณเป็นตัวเลขจำนวนคิว ไม่ใช่ยอดเงิน จึงรวมตอนแสดงผลได้ --}}
            <tr class="total">
                <td colspan="2" class="text-right">รวมจำนวน</td>
                <td class="text-center">{{ number_format($deliveryNote->details->sum('quantity'), 2) }}</td>
                <td class="text-center">คิว</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{--
        ===== ส่วนท้าย: สรุปยอด + ลายเซ็น =====
        ห้ามตัดหน้าตรงกลาง ถ้าที่ไม่พอ ทั้งก้อนจะย้ายไปหน้าถัดไปพร้อมกัน
        ลายเซ็นจะไม่หลุดไปอยู่หน้าเดียวโดด ๆ
    --}}
    <div style="page-break-inside: avoid;">

    <div class="receipt-note small">
        ได้รับสินค้าตามรายการข้างต้นครบถ้วนและอยู่ในสภาพเรียบร้อยแล้ว
    </div>

    @include('pdf.partials.signatures', ['slots' => [
        [
            'party' => 'customer',
            'label' => 'ผู้รับของ',
        ],
        [
            'party' => 'company',
            'label' => 'ผู้ส่งของ',
        ],
    ]])

    </div>{{-- /keep-together --}}

</body>

</html>
