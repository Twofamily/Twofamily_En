<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @font-face {
            font-family: 'sarabun';
            src: url("{{ public_path('fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }

        body {
            font-family: 'sarabun';
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .line {
            border-bottom: 1px solid #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #999;
        }

        .no-border td {
            border: none;
        }
    </style>
</head>

<body>

    @php
        $subTotal = $invoice->quotation->subtotal ?? 0;
        $discount = $invoice->quotation->discount ?? 0;

        $afterDiscount = max($subTotal - $discount, 0);
        $vat = $afterDiscount * 0.07;

        $grandTotal = $afterDiscount + $vat;
    @endphp

    <table class="no-border">
        <tr>
            <td>
                <div class="title">ใบแจ้งหนี้</div>
                <div class="sub-title">Invoice</div>
            </td>

            <td class="text-right">
                เลขที่: INV{{ str_pad($invoice->id_invoice, 5, '0', STR_PAD_LEFT) }}<br>
                @if ($invoice->deliveryNote)
                    อ้างอิงใบส่งของ: DN{{ str_pad($invoice->deliveryNote->id_delivery_note, 5, '0', STR_PAD_LEFT) }}<br>
                @endif
                วันที่: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}<br>
                ครบกำหนด:
                {{ \Carbon\Carbon::parse($invoice->created_at)->addDays((int) ($settings['credit_term'] ?? 7))->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="no-border">
        <tr>
            <td width="60%">
                <b>ลูกค้า:</b> {{ $invoice->customer->name_customer }}<br>
                <b>ที่อยู่:</b> {{ customer_address($invoice->customer) }}<br>
                <b>โทร:</b> {{ $invoice->customer->phone_customer }}<br>
            </td>

            <td width="40%">
                <b>บริษัท:</b> {{ $settings['company_name'] ?? '-' }}<br>
                <b>ที่อยู่:</b> {{ $settings['company_address'] ?? '-' }}<br>
                <b>โทร:</b> {{ $settings['company_phone'] ?? '-' }}<br>
                <b>เลขผู้เสียภาษี:</b> {{ $settings['tax_id'] ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รายละเอียด</th>
                <th>จำนวน</th>
                <th>หน่วย</th>
                <th>ราคา</th>
                <th>รวม</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($invoice->details as $i => $d)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d->product->name_product }}</td>
                    <td class="text-center">{{ $d->quantity }}</td>
                    <td class="text-center">คิว</td>
                    <td class="text-right">{{ number_format($d->price, 2) }}</td>
                    <td class="text-right">{{ number_format($d->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <table class="no-border">
        <tr>
            <td width="60%">
                <b>หมายเหตุ:</b><br>
                {!! nl2br(e($settings['invoice_note'] ?? '')) !!}
            </td>

            <td width="40%">
                <table>

                    <tr>
                        <td>รวม</td>
                        <td class="text-right">{{ number_format($subTotal, 2) }}</td>
                    </tr>

                    <tr>
                        <td>ส่วนลด</td>
                        <td class="text-right">
                            {{ number_format($discount, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>ยอดหลังหักส่วนลด</td>
                        <td class="text-right">
                            {{ number_format($afterDiscount, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>VAT 7%</td>
                        <td class="text-right">
                            {{ number_format($vat, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td><b>รวมสุทธิ</b></td>
                        <td class="text-right">
                            <b>{{ number_format($grandTotal, 2) }}</b>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="no-border">
        <tr>
            <td width="50%">
                <b>การชำระเงิน</b><br>
                ธนาคาร: {{ $settings['bank_name'] ?? '-' }}<br>
                ชื่อบัญชี: {{ $settings['bank_account_name'] ?? '-' }}<br>
                เลขบัญชี: {{ $settings['bank_account'] ?? '-' }}
            </td>

            <td width="50%" class="text-center">
                _________________________<br>
                ผู้รับใบแจ้งหนี้
            </td>
        </tr>
    </table>

</body>

</html>
