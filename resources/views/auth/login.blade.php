<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - مكتبة السلام</title>

    
    <link rel="stylesheet" href="{{ asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}"> <!-- أصبح محلياً هنا -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
         @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap'); /* يبقى للاحتياط إذا اتصل الإنترنت */


        :root {
            --primary-navy: #0e3854;
            --accent-cyan: #0d7cb5;
            --magenta-purple: #872061;
        }

       @font-face {
            font-family: 'Cairo';
            src: url('/fonts/SLXGc1gnrNjO4Q3GcaIv_C_E.woff2') format('woff2');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/SLXGc1gnrNjO4Q3GcaIv_M_E.woff2') format('woff2');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'Cairo';
            src: url('/fonts/SLXGc1gnrNjO4Q3GcaIv_GTE.woff2') format('woff2');
            font-weight: 700;
            font-style: normal;
        }

        /* تطبيق الخط كخيار أول أساسي في النظام */
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif !important;
        }
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background: linear-gradient(135deg, var(--primary-navy) 0%, #14577f 45%, var(--accent-cyan) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* subtle decorative shapes */
        body::before, body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(0px);
        }
        body::before {
            width: 420px;
            height: 420px;
            background: rgba(255,255,255,.06);
            top: -140px;
            right: -120px;
        }
        body::after {
            width: 320px;
            height: 320px;
            background: var(--magenta-purple);
            opacity: .18;
            bottom: -120px;
            left: -100px;
        }

        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
            animation: fadeUp .5s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(14, 56, 84, .35);
        }

        .login-card .top-bar {
            height: 6px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--magenta-purple));
        }

        .login-card .card-body {
            padding: 2.75rem 2.5rem 2.25rem;
        }

        .logo-badge {
            width: 92px;
            height: 92px;
            margin: 0 auto 1.1rem;
            background: #fff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(14, 56, 84, .15);
            padding: 10px;
        }

        .logo-badge img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .brand-title {
            font-weight: 800;
            color: var(--primary-navy);
            font-size: 1.5rem;
            margin-bottom: .15rem;
        }

        .brand-subtitle {
            color: #8a97a3;
            font-size: .85rem;
            margin-bottom: 1.75rem;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 700;
            color: var(--primary-navy);
            margin-bottom: .4rem;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            padding-inline-start: 2.6rem;
            border-radius: 10px;
            border: 1.5px solid #e5eaef;
            height: 50px;
            font-size: .95rem;
            transition: all .2s ease;
        }

        .input-group-custom .form-control:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 4px rgba(13, 124, 181, .12);
        }

        .input-group-custom .field-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            inset-inline-start: .85rem;
            color: #a9b4bd;
            font-size: 1.05rem;
            pointer-events: none;
        }

        .input-group-custom .toggle-password {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            inset-inline-end: .85rem;
            color: #a9b4bd;
            cursor: pointer;
            font-size: 1.05rem;
            background: none;
            border: none;
            padding: 0;
        }

        .form-check-input:checked {
            background-color: var(--accent-cyan);
            border-color: var(--accent-cyan);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-navy), var(--accent-cyan));
            border: none;
            border-radius: 10px;
            height: 52px;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            transition: all .2s ease;
            box-shadow: 0 8px 18px rgba(13, 124, 181, .3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(13, 124, 181, .4);
            color: #fff;
        }

        .alert-danger {
            border: none;
            border-radius: 10px;
            background: #fdeeee;
            color: #b3273e;
            border-inline-start: 4px solid #dc3545;
        }

        .footer-note {
            text-align: center;
            color: rgba(255,255,255,.75);
            font-size: .8rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="card login-card">
            <div class="top-bar"></div>
            <div class="card-body text-center">

                <div class="logo-badge">
                    <img src="{{ asset('images/logo.png') }}" alt="شعار مكتبة السلام">
                </div>

                <h3 class="brand-title">مكتبة السلام</h3>
                <p class="brand-subtitle">النظام الداخلي لإدارة المبيعات والمخازن</p>

                @if ($errors->any())
                    <div class="alert alert-danger p-3 text-start small mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf

                    <div class="mb-3 text-start">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <div class="input-group-custom">
                            <i class="bi bi-envelope-fill field-icon"></i>
                            <input type="email" name="email" id="email"
                                   class="form-control text-start" dir="ltr"
                                   placeholder="example@salam.dz"
                                   value="{{ old('email') }}" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock-fill field-icon"></i>
                            <input type="password" name="password" id="password"
                                   class="form-control text-start" dir="ltr"
                                   placeholder="••••••••" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye-slash-fill" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 form-check text-start d-flex align-items-center gap-1">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label text-muted small ms-1" for="remember">تذكرني في هذا الحاسوب</label>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        دخول للنظام <i class="bi bi-arrow-left-short"></i>
                    </button>
                </form>

            </div>
        </div>

        <p class="footer-note">© {{ date('Y') }} مكتبة السلام — جميع الحقوق محفوظة</p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
            }
        }
    </script>

</body>
</html>