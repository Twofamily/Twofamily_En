<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | {{ config('app.name', 'Two Family Engineering') }}</title>

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
            box-shadow: 0 6px 24px rgba(16, 24, 40, .08);
        }

        .card img {
            width: 130px;
            height: auto;
            display: block;
            margin: 0 auto 32px;
        }

        .alert {
            border-radius: 8px;
            padding: 12px 14px;
            font-size: .875rem;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .alert-success {
            background: #f0fdf4;
            color: #15803d;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: .9rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .field input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field input:focus {
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, .1);
        }

        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .9rem;
            color: #4b5563;
            cursor: pointer;
        }

        .remember input {
            width: 16px;
            height: 16px;
            accent-color: #1a1a1a;
            cursor: pointer;
        }

        .link {
            font-size: .875rem;
            color: #6b7280;
            text-decoration: none;
        }

        .link:hover {
            color: #1a1a1a;
            text-decoration: underline;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px 0;
            border: none;
            border-radius: 8px;
            background: #1a1a1a;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
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

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password"
                       required autocomplete="current-password">
            </div>

            <div class="row-between">
                <label class="remember">
                    <input type="checkbox" name="remember">
                    จดจำรหัสผ่าน
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link">ลืมรหัสผ่าน?</a>
                @endif
            </div>

            <button type="submit" class="btn">เข้าสู่ระบบ</button>
        </form>

    </div>

    <script>
        document.querySelectorAll('input[required]').forEach(function (input) {
            const messages = { email: 'กรุณากรอกอีเมล', password: 'กรุณากรอกรหัสผ่าน' };

            input.addEventListener('invalid', function () {
                if (input.validity.valueMissing) {
                    input.setCustomValidity(messages[input.name] ?? 'กรุณากรอกข้อมูลนี้');
                } else if (input.validity.typeMismatch) {
                    input.setCustomValidity('รูปแบบอีเมลไม่ถูกต้อง');
                }
            });

            input.addEventListener('input', function () {
                input.setCustomValidity('');
            });
        });
    </script>

</body>

</html>