<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>ใบเสนอราคา {{ $quotation->code_quot}}</title>

    <style>
        @font-face {
            font-family: 'sarabun';
            font-weight: normal;
            src: url("{{ public_path('fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }

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

        /* ---------- ส่วนลงชื่อท้ายเอกสาร ---------- */
        .signature-table {
            width: 100%;
            margin-top: 20px;
        }

        .signature-cell {
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-line {
            margin-top: 35px;
            border-bottom: 1px dashed #333;
            display: inline-block;
            min-width: 170px;
            padding-bottom: 2px;
            font-weight: bold;
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
                <td>{{ $settings['company.name'] ?? $settings['company_name'] ?? 'บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด' }}</td>
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
                
                {{-- เปลี่ยนมาดึงจาก $settings array แทนการใช้ helper setting() --}}
                <div class="company-name">
                    {{ $settings['company.name'] ?? $settings['company_name'] ?? 'บริษัท ทูแฟมิลี่ เอ็นจิเนียริ่ง จำกัด' }}
                </div>
                <div class="small">
                    {{ $settings['company.address'] ?? $settings['company_address'] ?? '-' }}<br>
                    โทร {{ $settings['company.phone'] ?? $settings['company_phone'] ?? '-' }}<br>
                    เลขประจำตัวผู้เสียภาษี {{ $settings['company.tax_id'] ?? $settings['tax_id'] ?? '-' }}
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
            <td>
                {{-- ป้องกัน Error หากไม่มี helper customer_address --}}
                @if(function_exists('customer_address'))
                    {{ customer_address($quotation->customer) }}
                @else
                    {{ $quotation->customer->address_customer ?? '-' }}
                @endif
            </td>
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
                    <td class="text-right">{{ number_format($d->total_price, 2) }}</td>
                </tr>
            @endforeach

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

    {{-- ===== ส่วนท้าย: สรุปยอด + พิมพ์ชื่อลงชื่อ ===== --}}
    <div style="page-break-inside: avoid;">

    <table width="100%" class="small" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 12px;">
                <div class="note">
                    <b>หมายเหตุ / เงื่อนไข</b><br>
                    {!! nl2br(e($settings['quotation_note'] ?? '')) !!}
                </div>
            </td>

            <td width="45%" style="vertical-align: top;">
                <table class="summary">
                    <tr>
                        <td>รวมเป็นเงิน</td>
                        <td class="text-right">{{ number_format($quotation->subtotal, 2) }}</td>
                    </tr>
                    @if ((float) $quotation->discount > 0)
                        <tr>
                            <td>หักส่วนลด</td>
                            <td class="text-right">{{ number_format($quotation->discount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>ยอดหลังหักส่วนลด</td>
                            <td class="text-right">{{ number_format($quotation->after_discount ?? ($quotation->subtotal - $quotation->discount), 2) }}</td>
                        </tr>
                    @endif
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

    {{-- ===== ลายเซ็นปรับเป็น พิมพ์ชื่อพนักงานไว้ตรงกลางเส้น (แบบใบเสร็จมาตรฐาน) ===== --}}
    <table class="signature-table small">
        <tr>
            <!-- ช่องซ้าย: ลูกค้า -->
            <td width="33%" class="signature-cell">
                <div>ในนาม <strong>ลูกค้า</strong></div>
                <div class="signature-line" style="font-weight: normal; color: #777;">
                    &nbsp;
                </div>
                <div class="muted" style="margin-top: 4px;">ผู้รับใบเสนอราคา</div>
                <div class="muted" style="font-size: 10.5px; margin-top: 2px;">วันที่ ...../...../..........</div>
            </td>

            <!-- ช่องกลาง: ผู้จัดทำ (พิมพ์ชื่อพนักงานจากระบบ) -->
            <td width="33%" class="signature-cell">
                <div>ในนาม <strong>ผู้จัดทำ</strong></div>
                <div class="signature-line">
                    {{ $quotation->issuer->name ?? '.....................................' }}
                </div>
                <div class="muted" style="margin-top: 4px;">ผู้จัดทำเอกสาร</div>
                <div class="muted" style="font-size: 10.5px; margin-top: 2px;">
                    วันที่ {{ $quotation->issued_at ? \Carbon\Carbon::parse($quotation->issued_at)->format('d/m/Y') : '-' }}
                </div>
            </td>

            <!-- ช่องขวา: ผู้อนุมัติ (พิมพ์ชื่อพนักงานจากระบบ) -->
            <td width="34%" class="signature-cell">
                <div>ในนาม <strong>{{ $settings['company.name'] ?? $settings['company_name'] ?? 'บริษัท' }}</strong></div>
                <div class="signature-line">
                    {{ $quotation->approver->name ?? '.....................................' }}
                </div>
                <div class="muted" style="margin-top: 4px;">ผู้อนุมัติ</div>
                <div class="muted" style="font-size: 10.5px; margin-top: 2px;">
                    วันที่ {{ $quotation->approved_at ? \Carbon\Carbon::parse($quotation->approved_at)->format('d/m/Y') : '-' }}
                </div>
            </td>
        </tr>
    </table>

    </div>{{-- /keep-together --}}

</body>

</html>