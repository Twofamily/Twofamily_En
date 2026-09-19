<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">

    @php
        // เลขที่อ่านจากคอลัมน์ code_inv (สร้างอัตโนมัติตอนบันทึก รูปแบบ INV-2569-0001)
        $invCode = $invoice->code_inv ?? '-';

        /*
         * ยอดเงิน: ใช้คอลัมน์ที่บันทึกไว้ในตาราง invoices ก่อน
         * ถ้ายังไม่มีคอลัมน์ จะถอยไปใช้วิธีเดิม (อ่านจากใบเสนอราคาแล้วคำนวณ)
         * เพื่อไม่ให้ไฟล์นี้พังระหว่างที่ยังไม่ได้เพิ่ม migration
         */
        $q = $invoice->quotation;

        $subTotal      = $invoice->subtotal ?? $q?->subtotal ?? 0;
        $discount      = $invoice->discount ?? $q?->discount ?? 0;
        $afterDiscount = $invoice->after_discount ?? max($subTotal - $discount, 0);
        $vatRate       = $invoice->vat_rate ?? 7;
        $vat           = $invoice->vat_amount ?? $afterDiscount * $vatRate / 100;
        $grandTotal    = $invoice->total_amount ?? $afterDiscount + $vat;

        /* วันที่: ใช้คอลัมน์ที่บันทึกไว้ก่อน ถ้าไม่มีจึงคำนวณแบบเดิม */
        $issueDate = \Carbon\Carbon::parse($invoice->invoice_date ?? $invoice->created_at);
        $dueDate   = $invoice->due_date
            ? \Carbon\Carbon::parse($invoice->due_date)
            : $issueDate->copy()->addDays((int) ($settings['credit_term'] ?? 7));
    @endphp

    <title>ใบแจ้งหนี้ {{ $invCode }}</title>

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
            font-size: 17px;
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

    <div class="footer">
        <table width="100%">
            <tr>
                <td>{{ $settings['company_name'] ?? '' }}</td>
                <td class="text-right">ใบแจ้งหนี้/ใบกำกับภาษีเลขที่ {{ $invCode }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== หัวเอกสาร: ข้อมูลบริษัท (ซ้าย) / กล่องเลขที่เอกสาร (ขวา) ===== --}}
    <table width="100%">
        <tr>
            <td width="60%" style="vertical-align: top;">
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

            <td width="40%" style="vertical-align: top;">
                <table class="doc-box small">
                    <tr>
                        <td colspan="2" class="doc-title">
                            ใบแจ้งหนี้ / ใบกำกับภาษี<br><span>INVOICE / TAX INVOICE</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-top: 6px;">เลขที่</td>
                        <td class="text-right" style="padding-top: 6px;"><b>{{ $invCode }}</b></td>
                    </tr>
                    <tr>
                        <td class="muted">วันที่</td>
                        <td class="text-right">{{ $issueDate->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding-bottom: 6px;">ครบกำหนดชำระ</td>
                        <td class="text-right" style="padding-bottom: 6px;"><b>{{ $dueDate->format('d/m/Y') }}</b></td>
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
            <td width="50%"><b>{{ $invoice->customer->name_customer ?? '-' }}</b></td>
            <td class="label divider" width="16%">ใบส่งของ</td>
            <td width="22%">
                {{ $invoice->deliveryNote?->code_dn ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">ที่อยู่</td>
            <td>{{ $invoice->customer ? customer_address($invoice->customer) : '-' }}</td>
            <td class="label divider">ใบเสนอราคา</td>
            <td>{{ $q?->code_quot ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">เลขภาษี</td>
            <td>{{ $invoice->customer->tax_id ?? '-' }}</td>
            <td class="divider"></td>
            <td></td>
        </tr>
        <tr>
            <td class="label">โทร</td>
            <td>{{ $invoice->customer->phone_customer ?? '-' }}</td>
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

            @foreach ($invoice->details as $d)
                <tr class="{{ $i % 2 === 0 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $d->product->name_product ?? '-' }}</td>
                    <td class="text-center">{{ $d->quantity }}</td>
                    <td class="text-center">คิว</td>
                    <td class="text-right">{{ number_format($d->price, 2) }}</td>
                    <td class="text-right">{{ number_format($d->total, 2) }}</td>
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
        ===== ส่วนท้าย: สรุปยอด + ลายเซ็น =====
        ห้ามตัดหน้าตรงกลาง ถ้าที่ไม่พอ ทั้งก้อนจะย้ายไปหน้าถัดไปพร้อมกัน
        ลายเซ็นจะไม่หลุดไปอยู่หน้าเดียวโดด ๆ
    --}}
    <div style="page-break-inside: avoid;">

    {{-- ===== ช่องทางชำระเงิน (ซ้าย) / สรุปยอด (ขวา) ===== --}}
    <table width="100%" class="small" style="margin-top: 10px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 12px;">
                <div class="box">
                    <b>ชำระเงินโดยโอนเข้าบัญชี</b>
                    <table width="100%" style="margin-top: 2px;">
                        <tr>
                            <td class="muted" width="22%">ธนาคาร</td>
                            <td>{{ $settings['bank_name'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="muted">ชื่อบัญชี</td>
                            <td>{{ $settings['bank_account_name'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="muted">เลขที่บัญชี</td>
                            <td><b>{{ $settings['bank_account'] ?? '-' }}</b></td>
                        </tr>
                    </table>
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
                        <td>หักส่วนลด</td>
                        <td class="text-right">{{ number_format($discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>มูลค่าสินค้า/บริการก่อนภาษี</td>
                        <td class="text-right">{{ number_format($afterDiscount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ภาษีมูลค่าเพิ่ม {{ rtrim(rtrim(number_format($vatRate, 2), '0'), '.') }}%</td>
                        <td class="text-right">{{ number_format($vat, 2) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>จำนวนเงินที่ต้องชำระ (บาท)</td>
                        <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- หมายเหตุเต็มความกว้างหน้า แต่ละข้อจะได้อยู่บรรทัดเดียว ไม่ถูกตัดเป็นสองบรรทัด --}}
    @if (!empty($settings['invoice_note']))
        <div class="note-full">
            <b>หมายเหตุ</b><br>
            {!! nl2br(e($settings['invoice_note'])) !!}
        </div>
    @endif

    @include('pdf.partials.signatures', ['slots' => [
        [
            'party' => 'customer',
            'label' => 'ผู้รับใบแจ้งหนี้',
        ],
        [
            'party' => 'company',
            'label' => 'ผู้ออกใบแจ้งหนี้',
        ],
    ]])

    </div>{{-- /keep-together --}}

</body>

</html>
