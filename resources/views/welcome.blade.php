<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Two Family Engineering') }}</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fa;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 460px;
            text-align: center;
            box-shadow: 0 6px 24px rgba(16, 24, 40, .08);
        }

        .card img {
            width: 150px;
            height: auto;
            display: block;
            margin: 0 auto 28px;
        }

        .card h1 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1a1a1a;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .card p {
            font-size: .9rem;
            color: #6b7280;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-block;
            width: 70%;
            padding: 12px 0;
            border-radius: 8px;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            transition: background .15s ease;
        }

        .btn:hover {
            background: #333;
        }
    </style>
</head>

<body>

    <div class="card">

        <img src="{{ asset('images/tfe-logo.png') }}" alt="TWO FAMILY ENGINEERING CO., LTD.">

        <h1>ระบบจัดการเอกสาร<br>และการประมาณค่าสิ้นเปลืองน้ำมันเชื้อเพลิง</h1>
        <p>บริษัท ทู แฟมิลี่ เอ็นจิเนียริ่ง จำกัด</p>

        @auth
            <a href="{{ url('/dashboard') }}" class="btn">เข้าสู่หน้าหลัก</a>
        @else
            <a href="{{ route('login') }}" class="btn">เข้าสู่ระบบ</a>
        @endauth

    </div>

</body>

</html>