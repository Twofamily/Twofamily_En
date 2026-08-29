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
            font-size: 14px;
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

        .sub-title {
            font-size: 16px;
        }

        .line {
            border-bottom: 1px solid #000;
            margin: 10px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #999;
            padding: 6px;
        }

        .small {
            font-size: 12px;
        }
    </style>
</head>

<body>

    <table width="100%">
        <tr>
            <td>
                <div class="title">ใบเสนอราคา</div>
                <div class="sub-title">Quotation</div>
            </td>

            <<td class="text-right small">
                เลขที่: QT{{ str_pad($quotation->id_quot, 5, '0', STR_PAD_LEFT) }}<br>

                วันที่: {{ \Carbon\Carbon::parse($quotation->date_quot)->format('d/m/Y') }}<br>

                ใช้ได้ถึง:
                {{ \Carbon\Carbon::parse($quotation->date_quot)->addDays((int) (setting('credit_term') ?? 14))->format('d/m/Y') }}
                </td>
        </tr>
    </table>

    <div class="line"></div>

    <table width="100%" class="small">
        <tr>
            <td width="60%">
                <b>ลูกค้า:</b> {{ $quotation->customer->name_customer }}<br>
                <b>ที่อยู่:</b> {{ $quotation->customer->address_detail }} {{ $quotation->customer->subdistrict }}
                {{ $quotation->customer->district }} {{ $quotation->customer->province }}
                {{ $quotation->customer->zipcode }}<br>
                <b>ติดต่อ:</b> {{ $quotation->customer->phone_customer }}
            </td>

            <td width="40%">
                <b>ผู้ออก:</b> {{ setting('company_name') }}<br>

                <b>ที่อยู่:</b> {{ setting('company_address') }}<br>

                <b>ติดต่อ:</b> {{ setting('company_phone') }}<br>

                <b>เลขผู้เสียภาษี:</b> {{ setting('tax_id') ?? '-' }}
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="table small">
        <thead>
            <tr>
                <th width="5%">ลำดับ</th>
                <th width="45%">รายละเอียด</th>
                <th width="10%">จำนวน</th>
                <th width="10%">หน่วย</th>
                <th width="15%">ราคาต่อหน่วย</th>
                <th width="15%">รวม</th>
            </tr>
        </thead>

        <tbody>
            @php
                $sum = 0;
                $i = 1;
            @endphp

            @foreach ($quotation->details as $d)
                @php
                    $total = $d->quantity * $d->price_per_unit;
                    $sum += $total;
                @endphp

                <tr>
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ $d->product->name_product }}</td>
                    <td class="text-center">{{ $d->quantity }}</td>
                    <td class="text-center">คิว</td>
                    <td class="text-right">{{ number_format($d->price_per_unit, 2) }}</td>
                    <td class="text-right">{{ number_format($total, 2) }}</td>
                </tr>
            @endforeach

            @for ($i; $i <= 8; $i++)
                <tr>
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

    @php
        $afterDiscount = max($sum - $quotation->discount, 0);
        $vat = round($afterDiscount * 0.07, 2);
        $grandTotal = $afterDiscount + $vat;
    @endphp

    <br>

    <table width="100%" class="small">
        <tr>
            <td width="60%">
                <b>หมายเหตุ:</b><br>
                {!! nl2br(e(setting('quotation_note'))) !!}
            </td>

            <td width="40%">
                <table width="100%">
                    <tr>
                        <td>รวม (บาท)</td>
                        <td class="text-right">{{ number_format($sum, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ส่วนลด (บาท)</td>
                        <td class="text-right">{{ number_format($quotation->discount, 2) }}</td>
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
                        <td><b>สุทธิ (บาท)</b></td>
                        <td class="text-right">
                            <b>{{ number_format($grandTotal, 2) }}</b>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="line"></div>

    <table width="100%" class="small">
        <tr>
            <td class="text-center">
                ___________________________<br>
                ผู้จัดทำ
            </td>
            <td class="text-center">
                ___________________________<br>
                ผู้อนุมัติ
            </td>
            <td class="text-center">
                ___________________________<br>
                ผู้รับใบเสนอราคา
            </td>
        </tr>
    </table>

</body>

</html>
