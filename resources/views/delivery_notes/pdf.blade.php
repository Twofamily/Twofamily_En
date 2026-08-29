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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 7px;
        }

        .no-border td {
            border: none;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <table class="no-border">
        <tr>
            <td>
                <div class="title">ใบส่งของ</div>
                <div>Delivery Note</div>
            </td>
            <td class="text-right">เลขที่:
                DN{{ str_pad($deliveryNote->id_delivery_note, 5, '0', STR_PAD_LEFT) }}<br>วันที่ส่ง:
                {{ $deliveryNote->delivery_date->format('d/m/Y') }}<br>
                @if ($deliveryNote->id_quotation)
                    อ้างอิง: QT{{ str_pad($deliveryNote->id_quotation, 5, '0', STR_PAD_LEFT) }}
                @endif
            </td>
        </tr>
    </table>

    <table class="no-border">
        <tr>
            <td><strong>ลูกค้า:</strong>
                {{ $deliveryNote->customer->name_customer ?? '-' }}<br><strong>ที่อยู่:</strong>
                {{ customer_address($deliveryNote->customer) }}</td>
            <td><strong>ผู้ออกเอกสาร:</strong> {{ setting('company_name') ?? '-' }}<br><strong>โทร:</strong>
                {{ setting('company_phone') ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รายการ</th>
                <th>จำนวน</th>
                <th>หน่วย</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryNote->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name_product ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                    <td class="text-center">คิว</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
    <table class="no-border">
        <tr>
            <td class="text-center">_________________________<br>ผู้ส่งของ</td>
            <td class="text-center">_________________________<br>ผู้รับของ</td>
        </tr>
    </table>
</body>

</html>
