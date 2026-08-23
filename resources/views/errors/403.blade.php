<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ไม่มีสิทธิ์เข้าถึง | ระบบจัดการเอกสาร</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 600;
            color: #212529;
            line-height: 1;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 text-center">

                <div class="error-code">403</div>

                <h4 class="mt-3 mb-3">ไม่มีสิทธิ์เข้าถึงหน้านี้</h4>

                <p class="text-muted mb-4">
                    {{ $exception->getMessage() ?: 'บัญชีของคุณไม่ได้รับอนุญาตให้เข้าถึงส่วนนี้ของระบบ' }}
                </p>

                @auth
                    <p class="text-muted small mb-4">
                        เข้าสู่ระบบในชื่อ <strong>{{ auth()->user()->name }}</strong>
                        &middot; สิทธิ์: {{ auth()->user()->role_name }}
                    </p>
                @endauth

                <div>
                    <a href="{{ route('dashboard') }}" class="btn btn-dark">กลับหน้าหลัก</a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">ย้อนกลับ</a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>