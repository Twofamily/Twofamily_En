<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

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

        /* ---------- ข้อมูลลูกค้า ---------- */
        .info {
            width: 100%;
            border: 1px solid #bfb3a8;
        }

        .info td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .info .label {
            width: 70px;
            color: #666;
        }

        .info .head {
            background-color: #efe9e3;
            font-weight: bold;
            border-bottom: 1px solid #bfb3a8;
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
                <td class="text-right">ใบเสนอราคาเลขที่ {{ $quotation->code_quot }}</td>
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
                            ใบเสนอราคา<br><span>QUOTATION</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-top: 6px;">เลขที่</td>
                        <td class="text-right" style="padding-top: 6px;"><b>{{ $quotation->code_quot }}</b></td>
                    </tr>
                    <tr>
                        <td class="muted">วันที่</td>
                        <td class="text-right">{{ \Carbon\Carbon::parse($quotation->date_quot)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        {{-- ใช้ end_quot ที่บันทึกไว้ในเอกสาร ไม่คำนวณสดตอนพิมพ์ --}}
                        <td class="muted" style="padding-bottom: 6px;">ยืนราคาถึง</td>
                        <td class="text-right" style="padding-bottom: 6px;">
                            {{ $quotation->end_quot
                                ? \Carbon\Carbon::parse($quotation->end_quot)->format('d/m/Y')
                                : '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ===== ข้อมูลลูกค้า ===== --}}
    <table class="info small">
        <tr>
            <td colspan="2" class="head">เสนอราคาให้ (ลูกค้า)</td>
        </tr>
        <tr>
            <td class="label">ชื่อ</td>
            <td><b>{{ $quotation->customer->name_customer }}</b></td>
        </tr>
        <tr>
            <td class="label">ที่อยู่</td>
            <td>{{ customer_address($quotation->customer) }}</td>
        </tr>
        <tr>
            <td class="label">โทร</td>
            <td>{{ $quotation->customer->phone_customer ?: '-' }}</td>
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

            @foreach ($quotation->details as $d)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $d->product->name_product }}</td>
                    <td class="text-center">{{ $d->quantity }}</td>
                    <td class="text-center">คิว</td>
                    <td class="text-right">{{ number_format($d->price_per_unit, 2) }}</td>
                    {{-- อ่านจาก total_price ที่บันทึกไว้ ไม่คูณใหม่ตอนพิมพ์ --}}
                    <td class="text-right">{{ number_format($d->total_price, 2) }}</td>
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
                    <b>หมายเหตุ / เงื่อนไข</b><br>
                    {!! nl2br(e(setting('quotation_note'))) !!}
                </div>
            </td>

            <td width="45%" style="vertical-align: top;">
                {{--
                    ยอดเงินทุกบรรทัดอ่านจากคอลัมน์ที่บันทึกไว้ตอนออกเอกสาร
                    ไม่คำนวณสดตอนพิมพ์ เพื่อให้เอกสารเก่าพิมพ์ซ้ำได้เหมือนเดิม
                    แม้ราคาสินค้าหรืออัตรา VAT จะเปลี่ยนไปแล้ว
                --}}
                <table class="summary">
                    <tr>
                        <td>รวมเป็นเงิน</td>
                        <td class="text-right">{{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>หักส่วนลด</td>
                        <td class="text-right">{{ number_format($quotation->discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ยอดหลังหักส่วนลด</td>
                        <td class="text-right">{{ number_format($quotation->after_discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ภาษีมูลค่าเพิ่ม {{ rtrim(rtrim(number_format($quotation->vat_rate, 2), '0'), '.') }}%</td>
                        <td class="text-right">{{ number_format($quotation->vat_amount, 2) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>จำนวนเงินสุทธิ (บาท)</td>
                        <td class="text-right">{{ number_format($quotation->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if (function_exists('baht_text'))
        <div class="baht-text small" style="margin-top: 8px;">
            ({{ baht_text($quotation->total_amount) }})
        </div>
    @endif

    @include('pdf.partials.signatures', ['slots' => [
        [
            'party' => 'customer',
            'label' => 'ผู้รับใบเสนอราคา',
        ],
        [
            'party' => 'company',
            'label' => 'ผู้จัดทำ',
            'user'  => $quotation->issuer,
            'date'  => $quotation->issued_at,
        ],
        [
            'party' => 'company',
            'label' => 'ผู้อนุมัติ',
            'user'  => $quotation->approver,
            'date'  => $quotation->approved_at,
        ],
    ]])

    </div>{{-- /keep-together --}}

</body>

</html>
