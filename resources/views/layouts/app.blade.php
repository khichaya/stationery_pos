<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة السلام - النظام الداخلي</title>

      
 
     <link rel="stylesheet" href="{{ asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
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
* :not(.bi) {
            font-family: 'Cairo', sans-serif !important;
        }
        /* تطبيق الخط كخيار أول أساسي في النظام */
        body {
            background-color: var(--bg);
            font-family: 'Cairo', sans-serif !important;
            -webkit-font-smoothing: antialiased;
        }

        :root {
            --brand-1: #1e3a5f;
            --brand-2: #2c5f8a;
            --accent: #f0a500;
            --bg: #f4f6fa;
        }


        body {
            background-color: var(--bg);
        }

        /* ===== Navbar ===== */
        .navbar {
            background: linear-gradient(135deg, var(--brand-1) 0%, var(--brand-2) 100%);
            box-shadow: 0 4px 16px rgba(30, 58, 95, 0.18);
            padding-top: .65rem;
            padding-bottom: .65rem;
        }

        .navbar-brand {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .navbar-brand .icon-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,.12);
            border-radius: 10px;
            margin-inline-end: 8px;
        }

       .navbar-nav .nav-link {
            font-family: 'Cairo', sans-serif !important;
            font-weight: 700 !important; /* لجعل التبويبات واضحة ومقروءة بشكل أنيق */
            font-size: .95rem;
            padding: .55rem 1rem !important;
            border-radius: 8px;
            color: rgba(255,255,255,.85) !important;
            transition: all .18s ease;
            margin: 0 2px;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255,255,255,.1);
            color: #fff !important;
        }

        .navbar-nav .nav-link.active-link {
            background: rgba(255,255,255,.16);
            color: #fff !important;
        }

        .nav-link.pos-highlight {
            background: var(--accent);
            color: #1e3a5f !important;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(240,165,0,.4);
        }

        .nav-link.pos-highlight:hover {
            background: #ffb61f;
            color: #1e3a5f !important;
            transform: translateY(-1px);
        }

        .user-chip {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 30px;
            padding: .35rem .5rem .35rem 1rem;
        }

        .user-chip .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--accent);
            color: #1e3a5f;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
        }

        .btn-logout {
            border-radius: 30px;
            font-weight: 600;
            padding: .3rem .9rem;
        }

        /* ===== Cards (global) ===== */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 6px 18px rgba(30,58,95,.06);
        }

        main.container-fluid {
            padding-top: .5rem;
            padding-bottom: 2.5rem;
        }
    </style>

    @livewireStyles
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
           <a class="navbar-brand d-flex align-items-center" href="/dashboard">
    @php
        $setting = \App\Models\InstitutionSetting::first();
    @endphp
    
    @if($setting && $setting->logo_path)
        <img src="{{ Storage::url($setting->logo_path) }}" 
             alt="logo" 
             style="height: 32px; width: 32px; object-fit: contain; margin-left: 8px; border-radius: 4px;">
    @else
        <span class="icon-badge"><i class="bi bi-book-half"></i></span>
    @endif
    
    {{ $setting->name ?? 'مكتبة السلام' }}
</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                @auth
                @php
                    $user = auth()->user();
                    $perms = is_array($user->permissions) ? $user->permissions : (json_decode($user->permissions, true) ?? []);
                    $isManager = $user->role === 'manager';
                @endphp

                <ul class="navbar-nav me-auto mb-2 mb-lg-0" style="font-size: 0.88rem;">
                   <!-- 🛡️ زر شاشة الرئيسية يعتمد الآن على الصلاحية المخصصة بالكامل -->
@if($isManager || in_array('access_dashboard', $perms))
<li class="nav-item">
    <a class="nav-link py-1 {{ request()->is('dashboard') ? 'active-link' : '' }}" href="/dashboard">
        <i class="bi bi-house-door me-1"></i> الرئيسية
    </a>
</li>
@endif
                    
                    @if($isManager || in_array('access_pos', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 pos-highlight {{ request()->is('pos') ? 'active-link' : '' }}" href="/pos">
                            <i class="bi bi-cart3 me-1"></i> البيع
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('manage_products', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 {{ request()->is('products*') ? 'active-link' : '' }}" href="/products">
                            <i class="bi bi-box-seam me-1"></i> المخزن 
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('manage_services', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 fw-bold {{ request()->is('services*') ? 'active-link' : '' }}" href="{{ route('services.index') }}">
                            <i class="bi bi-bag-plus me-1"></i> الخدمات
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('manage_expenses', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 fw-bold {{ request()->is('expenses*') ? 'active-link' : '' }}" href="{{ route('expenses.index') }}">
                            <i class="bi bi-cash-stack me-1"></i> المصاريف
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('view_dashboard_stats', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 fw-bold {{ request()->is('sales/archive') ? 'active-link' : '' }}" href="{{ route('sales.archive') }}">
                            <i class="bi bi-archive me-1"></i> الأرشيف
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('manage_debts', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 {{ request()->is('customers*') ? 'active-link' : '' }}" href="{{ route('customers.index') }}">
                            <i class="bi bi-people me-1"></i> الديون
                        </a>
                    </li>
                    @endif
                    
                    @if($isManager || in_array('view_dashboard_stats', $perms))
                    <li class="nav-item">
                        <a class="nav-link py-1 {{ request()->is('reports*') ? 'active-link' : '' }}" href="{{ route('reports.index') }}">
                            <i class="bi bi-bar-chart-line me-1"></i> التقارير
                        </a>
                    </li>
                    @endif
                </ul>
                @endauth

                <div class="dropdown d-none d-lg-inline-block border-start ps-3">
                    <button class="btn p-0 border-0 dropdown-toggle text-decoration-none d-flex align-items-center gap-2" 
                            type="button" 
                            id="adminDropdown" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        
                        <div class="user-chip d-flex align-items-center gap-2">
                            <span class="text-white small fw-bold">
                                مرحباً، {{ auth()->user()->name ?? 'المدير العام' }}
                            </span>
                            <span class="avatar bg-primary text-light d-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; font-size: 0.85rem; font-weight: bold;">
                                {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
                            </span>
                        </div>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="adminDropdown" style="font-size: 0.85rem; min-width: 220px;">
                        
    <li class="dropdown-header text-muted small border-bottom mb-1 pb-2">
        <i class="bi bi-gear-fill me-1"></i> الإعدادات الإدارية
    </li>

    @if(auth()->user() && auth()->user()->role === 'manager')
    <li>
        <a class="dropdown-item py-2 {{ request()->is('settings/invoice-identity') ? 'active' : '' }}" href="/settings/invoice-identity">
            <i class="bi bi-receipt-cutoff text-dark me-2"></i> إعدادات هيدر وفوتر الفاتورة
        </a>
    </li>
    
    <li>
        <a class="dropdown-item py-2 {{ request()->is('users-management') ? 'active' : '' }}" href="/users-management">
            <i class="bi bi-shield-lock text-primary me-2"></i> المستخدمين والصلاحيات
        </a>
    </li>
    @endif
    
    @if(auth()->user() && (auth()->user()->role === 'manager' || in_array('Database_backup', is_array(auth()->user()->permissions) ? auth()->user()->permissions : (json_decode(auth()->user()->permissions, true) ?? []))))
    <li>
        <a class="dropdown-item py-2 {{ request()->is('database-backups') ? 'active' : '' }}" href="/database-backups">
            <i class="bi bi-cloud-arrow-up text-success me-2"></i> النسخ الاحتياطي لـ الـ DB
        </a>
    </li>
    @endif
    
    <li><hr class="dropdown-divider opacity-50"></li>
    
    <li>
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="dropdown-item py-2 text-danger fw-bold">
                <i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج
            </button>
        </form>
    </li>
</ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container-fluid px-4">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    @livewireScripts
    <livewire:schedule-heartbeat />
</body>
</html>