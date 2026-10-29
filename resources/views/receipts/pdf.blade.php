<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">

    @php
        $inv      = $receipt->invoice;
        $customer = $receipt->payer();
        $lines    = $receipt->lines();

        // เลขที่อ่านจากคอลัมน์ code_rc (สร้างอัตโนมัติตอนบันทึก รูปแบบ RC-2569-0001)
        $rcCode = $receipt->code_rc ?? '-';

        $docTitle = ['ใบเสร็จรับเงิน', 'RECEIPT'];

        // ยอดที่บันทึกไว้ในใบเสร็จ (ใบเก่าที่ยังไม่มีคอลัมน์ ถอยไปใช้ยอดของใบแจ้งหนี้)
        $subTotal   = (float) $receipt->subtotal ?: ($inv?->itemsSubtotal() ?? 0);
        $vatRate    = \App\Models\Invoice::VAT_RATE;
        $vat        = (float) $receipt->vat_amount ?: ($inv?->itemsVat() ?? 0);
        $gross      = round($subTotal + $vat, 2);
        $grandTotal = (float) $receipt->total;

        // วิธีชำระ: ติ๊กจากหลักฐานการชำระรายการแรก
        $proof      = $receipt->proofPayments()->first();
        $method     = $proof?->method;
        $isCash     = $method === 'cash';
        $isTransfer = $method === 'transfer';
        $isCheque   = $method === 'cheque';
    @endphp

    <title>ใบเสร็จรับเงิน {{ $rcCode }}</title>

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

        .box {
            border: 1px solid #bfb3a8;
            padding: 6px 8px;
        }

        /* ช่องติ๊ก: ติ๊กแล้วจะเป็นช่องทึบสีน้ำตาล */
        .check {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #444;
            margin-right: 4px;
        }

        .check.on {
            background-color: #4a3526;
            border-color: #4a3526;
        }

        /* ---------- หมายเหตุเต็มความกว้าง ---------- */
        .note-full {
            border: 1px solid #bfb3a8;
            padding: 5px 8px;
            margin-top: 8px;
            font-size: 10.5px;
            line-height: 1.3;
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
    @include('partials.pdf-cancelled', ['doc' => $receipt])

    <div class="footer">
        <table width="100%">
            <tr>
                <td>{{ $settings['company_name'] ?? '' }}</td>
                <td class="text-right">ใบเสร็จรับเงินเลขที่ {{ $rcCode }}</td>
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
                <div class="company-name">{{ $settings['company_name'] ?? '-' }}</div>
                <div class="small">
                    {{ $settings['company_address'] ?? '-' }}<br>
                    โทร {{ $settings['company_phone'] ?? '-' }}<br>
                    เลขประจำตัวผู้เสียภาษี {{ $settings['tax_id'] ?? '-' }}
                </div>
            </td>

            <td width="38%" style="vertical-align: top;">
                <table class="doc-box small">
                    <tr>
                        <td colspan="2" class="doc-title">
                            {{ $docTitle[0] }}<br><span>{{ $docTitle[1] }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-top: 6px;">เลขที่</td>
                        <td class="text-right" style="padding-top: 6px;"><b>{{ $rcCode }}</b></td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-bottom: 6px;">วันที่รับเงิน</td>
                        <td class="text-right" style="padding-bottom: 6px;">
                            {{ $receipt->date_receipt
                                ? \Carbon\Carbon::parse($receipt->date_receipt)->format('d/m/Y')
                                : '-' }}
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
            <td colspan="2" class="head" width="62%">ได้รับเงินจาก</td>
            <td colspan="2" class="head divider" width="38%">เอกสารอ้างอิง</td>
        </tr>
        <tr>
            <td class="label" width="12%">ชื่อ</td>
            <td width="50%"><b>{{ $customer->name_customer ?? '-' }}</b></td>
            <td class="label divider" width="16%">ใบแจ้งหนี้</td>
            <td width="22%">
                {{ $inv?->code_inv ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">ที่อยู่</td>
            <td>{{ $customer ? customer_address($customer) : '-' }}</td>
            <td class="label divider">ใบส่งของ</td>
            <td>
                {{ $inv?->deliveryNotes->pluck('code_dn')->implode(', ') ?: '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">เลขภาษี</td>
            <td>{{ customer_tax_line($customer) }}</td>
            <td class="label divider">แคมป์งาน</td>
            <td>{{ $receipt->camp->code_camp ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">โทร</td>
            <td>{{ $customer->phone_customer ?? '-' }}</td>
            <td class="label divider">ประเภท</td>
            <td>{{ $receipt->type_label }}</td>
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

            @foreach ($lines as $line)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $line['name'] }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($line['quantity'], 2), '0'), '.') }}</td>
                    <td class="text-center">{{ $line['unit'] }}</td>
                    <td class="text-right">{{ number_format($line['price'], 2) }}</td>
                    <td class="text-right">{{ number_format($line['total'], 2) }}</td>
                </tr>
            @endforeach

            {{-- เติมแถวว่างให้ตารางเต็มหน้า --}}
            @for ($i; $i <= 6; $i++)
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
        ===== ส่วนท้าย: สรุปยอด + หมายเหตุ + ลายเซ็น =====
        ห่อไว้ในก้อนเดียวและห้ามตัดหน้าตรงกลาง ถ้าที่เหลือในหน้าไม่พอ
        ทั้งก้อนจะย้ายไปหน้าถัดไปพร้อมกัน ลายเซ็นจะไม่หลุดไปอยู่หน้าเดียวโดด ๆ
        ส่วนนี้จึงต้องเตี้ยพอที่จะอยู่หน้าแรกได้
    --}}
    <div style="page-break-inside: avoid;">

    {{-- ===== วิธีชำระเงิน (ซ้าย) / สรุปยอด (ขวา) ===== --}}
    <table width="100%" class="small" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 12px;">
                <div class="box">
                    <b>ชำระโดย</b>
                    &nbsp;&nbsp;
                    <span class="check {{ $isCash ? 'on' : '' }}"></span>เงินสด
                    &nbsp;&nbsp;
                    <span class="check {{ $isTransfer ? 'on' : '' }}"></span>โอนเงิน
                    &nbsp;&nbsp;
                    <span class="check {{ $isCheque ? 'on' : '' }}"></span>เช็ค
                    @php
                        // โอนเข้าบัญชีบริษัท → เลขที่ = เลขบัญชีที่ตั้งไว้ใน ตั้งค่า > การรับชำระเงิน
                        // เช็ค → เลขที่ = เลขเช็ค (reference_no)
                        $companyBank = trim((string) \App\Models\Setting::get('payment.bank_name', ''));
                        $companyAcc  = trim((string) \App\Models\Setting::get('payment.account_no', ''));
                        $toCompany   = $isTransfer && $companyAcc !== ''
                                       && ($proof?->bank_name === null || trim($proof->bank_name) === '' || trim($proof->bank_name) === $companyBank);
                        $bankText    = $proof?->bank_name ?: ($toCompany ? $companyBank : '');
                        $numberText  = $toCompany ? $companyAcc : ($proof?->reference_no ?: '');
                    @endphp
                    <div style="margin-top: 5px;" class="muted">
                        ธนาคาร {{ $bankText ?: '.........................' }}
                        {{ $toCompany ? 'เลขที่บัญชี' : 'เลขที่' }} {{ $numberText ?: '.........................' }}
                        @if ($toCompany && $proof?->reference_no)
                            <br>เลขอ้างอิง {{ $proof->reference_no }}
                        @endif
                        <br>
                        ลงวันที่ {{ $proof?->paid_at?->format('d/m/Y') ?? '.........................' }}
                    </div>
                </div>

                @if (function_exists('baht_text'))
                    <div class="baht-text" style="margin-top: 6px;">
                        ({{ baht_text($grandTotal) }})
                    </div>
                @endif
            </td>

            <td width="45%" style="vertical-align: top;">
                <table class="summary">
                    <tr>
                        <td>รวมเป็นเงิน</td>
                        <td class="text-right">{{ number_format($subTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ภาษีมูลค่าเพิ่ม {{ rtrim(rtrim(number_format($vatRate, 2), '0'), '.') }}%</td>
                        <td class="text-right">{{ number_format($vat, 2) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>จำนวนเงินที่ได้รับ (บาท)</td>
                        <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- หมายเหตุเต็มความกว้างหน้า แต่ละข้อจะได้อยู่บรรทัดเดียว ไม่ถูกตัดเป็นสองบรรทัด --}}
    @if (!empty($settings['receipt_note']))
        <div class="note-full">
            <b>หมายเหตุ</b><br>
            {!! nl2br(e($settings['receipt_note'])) !!}
        </div>
    @endif

    @include('pdf.partials.signatures', ['slots' => [
        [
            'party' => 'customer',
            'label' => 'ผู้จ่ายเงิน',
        ],
        [
            'party' => 'company',
            'label' => 'ผู้รับเงิน',
            'user'  => $receipt->issuer,      // ผู้ออกเอกสาร: มีลายเซ็นแสดงรูป ไม่มีเว้นว่าง
            'date'  => $receipt->date_receipt,
        ],
    ]])

    </div>{{-- /keep-together --}}

</body>

</html>
