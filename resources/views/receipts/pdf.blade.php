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
        $inv = $receipt->invoice;

        $subTotal = $inv->quotation->subtotal ?? 0;
        $discount = $inv->quotation->discount ?? 0;

        $afterDiscount = max($subTotal - $discount, 0);
        $vat = $afterDiscount * 0.07;

        $grandTotal = $afterDiscount + $vat;
    @endphp

    <!-- HEADER -->
    <table class="no-border">
        <tr>
            <td>
                <div class="title">ใบเสร็จรับเงิน</div>
                <div>Receipt</div>
            </td>

            <td class="text-right">
                เลขที่: RC{{ str_pad($receipt->id_receipt, 5, '0', STR_PAD_LEFT) }}<br>
                วันที่รับเงิน: {{ \Carbon\Carbon::parse($receipt->date_receipt)->format('d/m/Y') }}<br>
                อ้างอิง: INV{{ str_pad($inv->id_invoice, 5, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <!-- CUSTOMER -->
    <table class="no-border">
        <tr>
            <td width="60%">
                <b>ลูกค้า:</b> {{ $inv->customer->name_customer }}<br>
                <b>ที่อยู่:</b> {{ customer_address($inv->customer) }}<br>
                <b>โทร:</b> {{ $inv->customer->phone_customer }}<br>
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

    <!-- ITEMS -->
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
            @foreach ($inv->details as $i => $d)
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

    <!-- TOTAL -->
    <table class="no-border">
        <tr>
            <td width="60%">
                <b>หมายเหตุ:</b><br>
                {!! nl2br(e($settings['receipt_note'] ?? '')) !!}
            </td>

            <td width="40%">
                <table>

                    <tr>
                        <td>รวม</td>
                        <td class="text-right">{{ number_format($subTotal, 2) }}</td>
                    </tr>

                    <tr>
                        <td>ส่วนลด</td>
                        <td class="text-right">{{ number_format($discount, 2) }}</td>
                    </tr>

                    <tr>
                        <td>ยอดหลังหักส่วนลด</td>
                        <td class="text-right">{{ number_format($afterDiscount, 2) }}</td>
                    </tr>

                    <tr>
                        <td>VAT 7%</td>
                        <td class="text-right">{{ number_format($vat, 2) }}</td>
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

    <!-- PAYMENT -->
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
                ผู้รับเงิน
            </td>
        </tr>
    </table>

</body>

</html>
