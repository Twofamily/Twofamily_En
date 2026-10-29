<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::get('company.name_th') ?: config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/action-buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('js/vendor/flatpickr/flatpickr.min.css') }}">
    <script>
        (function() {
            try {
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {
            }
        })();
    </script>

    <style>
        :root {
            --sidebar-bg: linear-gradient(180deg, #2a2a2a 0%, #141414 100%);
            --sidebar-text: #e5e7eb;
            --sidebar-hover: rgba(255, 255, 255, 0.12);
            --active-bg: #f8fafc;
            --active-text: #141414;
            --sidebar-w: 280px;
            --sidebar-w-collapsed: 78px;
        }

        html.sidebar-collapsed {
            --sidebar-w: var(--sidebar-w-collapsed);
        }

        body {
            background-color: #f5f7fa;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            overflow-x: hidden;
            overflow-y: auto;
            z-index: 1040;
            transition: width .25s ease, transform .25s ease;
        }

        .main-area {
            margin-left: var(--sidebar-w);
            height: 100vh;
            overflow-y: auto;
            padding: 24px;
            transition: margin-left .25s ease;
        }

        .sidebar-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 6px 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .sidebar-toggle {
            flex: 0 0 auto;
            background: transparent;
            border: none;
            color: var(--sidebar-text);
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 1.2rem;
            line-height: 1;
            cursor: pointer;
            transition: background-color .2s ease;
        }

        .sidebar-toggle:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        .brand {
            flex: 1 1 auto;
            min-width: 0;
            color: #fff;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 10px;
            white-space: nowrap;
            overflow: hidden;
        }

        .brand:hover {
            background-color: transparent !important;
        }

        .brand-line-1 {
            display: block;
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.2px;
        }

        .brand-line-2 {
            display: block;
            font-size: .82rem;
            font-weight: 400;
            opacity: .7;
            line-height: 1.3;
            margin-top: 1px;
        }

        html.sidebar-collapsed .sidebar-head {
            justify-content: center;
            padding: 16px 0 18px;
        }

        html.sidebar-collapsed .brand {
            display: none;
        }

        .sidebar a {
            transition: all .25s ease;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar .nav-link {
            border-radius: 18px;
            padding: 12px 18px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: inherit;
            white-space: nowrap;
        }

        .sidebar .nav-link.active {
            background-color: var(--active-bg);
            color: var(--active-text);
            font-weight: 700;
        }

        .arrow {
            font-size: .8rem;
            transition: transform .25s ease;
        }

        [aria-expanded="true"] .arrow {
            transform: rotate(180deg);
        }

        .submenu {
            padding-left: 12px;
            margin-top: 2px;
            margin-bottom: 6px;
        }

        .sub-link {
            display: block;
            border-radius: 14px;
            padding: 10px 16px;
            font-size: .95rem;
            margin-bottom: 4px;
            color: inherit;
            white-space: nowrap;
        }

        .sub-link.active {
            background-color: rgba(255, 255, 255, 0.95);
            color: var(--active-text);
            font-weight: 600;
        }

        html.sidebar-collapsed .menu-text,
        html.sidebar-collapsed .arrow {
            display: none;
        }

        html.sidebar-collapsed .sidebar .nav-link {
            justify-content: center;
            padding: 12px 0;
        }

        html.sidebar-collapsed .sidebar .nav-link i.me-2 {
            margin-right: 0 !important;
            font-size: 1.25rem;
        }

        html.sidebar-collapsed .submenu {
            display: none;
        }

        .user-menu {
            background: var(--sidebar-bg);
            border-radius: 14px;
            padding: 6px;
            border: none;
        }

        .user-menu .dropdown-item {
            color: var(--sidebar-text);
            border-radius: 10px;
        }

        html.sidebar-collapsed .user-block .dropdown-toggle::after {
            display: none;
        }

        .content-area {
            background-color: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
        }

        @media (max-width: 767.98px) {
            .sidebar {
                width: 280px;
                transform: translateX(-100%);
            }

            html.sidebar-mobile-open .sidebar {
                transform: translateX(0);
            }

            html.sidebar-collapsed .brand {
                display: block;
            }

            html.sidebar-collapsed .brand-short {
                display: none;
            }

            html.sidebar-collapsed .menu-text,
            html.sidebar-collapsed .arrow {
                display: inline;
            }

            html.sidebar-collapsed .submenu {
                display: block;
            }

            html.sidebar-collapsed .sidebar .nav-link {
                justify-content: space-between;
                padding: 12px 18px;
            }

            .main-area {
                margin-left: 0;
                padding: 16px;
            }

            .mobile-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .45);
                z-index: 1035;
                display: none;
            }

            html.sidebar-mobile-open .mobile-backdrop {
                display: block;
            }
        }

        .mobile-open-btn {
            display: none;
        }

        @media (max-width: 767.98px) {
            .mobile-open-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .sidebar,
            .main-area {
                transition: none;
            }
        }
    </style>
</head>

<body>

    <div class="mobile-backdrop" data-sidebar-close></div>

    <div class="sidebar">
        <div class="d-flex flex-column px-3 min-vh-100">
            <div class="sidebar-head">
                <a href="{{ route('dashboard') }}" class="brand">
                    <span class="brand-line-1">บริษัททูแฟมิลี่</span>
                    <span class="brand-line-2">เอ็นจิเนียริ่ง จำกัด</span>
                </a>

                <button type="button" class="sidebar-toggle" id="sidebarToggle" title="ย่อ/ขยายเมนู"
                    aria-label="ย่อหรือขยายเมนูด้านข้าง">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            @php
                $authUser = auth()->user();
                $canSee = fn(string $module) => $authUser && $authUser->canViewModule($module);

                $isProductPage = request()->routeIs('products.*') || request()->routeIs('product_types.*');

                $isTruckPage =
                    request()->routeIs('truck_brands.*') ||
                    request()->routeIs('truck_models.*') ||
                    request()->routeIs('trucks.*');

                $isDocPage =
                    request()->routeIs('quotations.*') ||
                    request()->routeIs('sales_orders.*') ||
                    request()->routeIs('delivery_notes.*') ||
                    request()->routeIs('invoices.*') ||
                    request()->routeIs('receipts.*') ||
                    request()->routeIs('tax-invoices.*');

                $isUserPage =
                    request()->routeIs('users.*') ||
                    request()->routeIs('drivers.*') ||
                    request()->routeIs('customers.*');

                $showProductMenu = $canSee('product_types') || $canSee('products');
                $showTruckMenu = $canSee('truck_brands') || $canSee('truck_models') || $canSee('trucks');
                $showDocMenu =
                    $canSee('quotations') ||
                    $canSee('sales_orders') ||
                    $canSee('delivery_notes') ||
                    $canSee('invoices') ||
                    $canSee('receipts');
                $showUserMenu =
                    ($authUser && $authUser->isAdmin()) || $canSee('drivers') || $canSee('customers');
            @endphp

            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="แดชบอร์ด">
                        <span><i class="bi bi-speedometer2 me-2"></i><span class="menu-text">แดชบอร์ด</span></span>
                    </a>
                </li>
                @if ($canSee('camps'))
                    <li class="nav-item">
                        <a href="{{ route('camps.index') }}"
                            class="nav-link {{ request()->routeIs('camps.*') ? 'active' : '' }}" title="แคมป์งาน">
                            <span><i class="bi bi-geo-alt me-2"></i><span class="menu-text">แคมป์งาน</span></span>
                        </a>
                    </li>
                @endif

                @if ($showProductMenu)
                    @php
                        $productMenuUrl = $canSee('products')
                            ? route('products.index')
                            : route('product_types.index');
                    @endphp
                    <li class="nav-item">
                        <a href="{{ $productMenuUrl }}"
                            class="nav-link {{ $isProductPage ? 'active' : '' }}" title="สินค้า">
                            <span><i class="bi bi-box-seam me-2"></i><span class="menu-text">สินค้า</span></span>
                        </a>
                    </li>
                @endif

                @if ($canSee('fuel_records'))
                    <li class="nav-item">
                        <a href="{{ route('fuel_records.index') }}"
                            class="nav-link {{ request()->routeIs('fuel_records.*') ? 'active' : '' }}"
                            title="ต้นทุนค่าน้ำมัน">
                            <span><i class="bi bi-fuel-pump me-2"></i><span class="menu-text">ต้นทุนค่าน้ำมัน</span></span>
                        </a>
                    </li>
                @endif

                @if ($showTruckMenu)
                    @php
                        $truckMenuUrl = $canSee('trucks')
                            ? route('trucks.index')
                            : ($canSee('truck_models') ? route('truck_models.index') : route('truck_brands.index'));
                    @endphp
                    <li class="nav-item">
                        <a href="{{ $truckMenuUrl }}"
                            class="nav-link {{ $isTruckPage ? 'active' : '' }}" title="รถบรรทุก">
                            <span><i class="bi bi-truck me-2"></i><span class="menu-text">รถบรรทุก</span></span>
                        </a>
                    </li>
                @endif

                @if ($showDocMenu)
                    <li class="nav-item">
                        <a class="nav-link {{ $isDocPage ? 'active' : '' }}" data-bs-toggle="collapse"
                            data-submenu-toggle href="#docMenu" role="button" title="เอกสาร"
                            aria-expanded="{{ $isDocPage ? 'true' : 'false' }}" aria-controls="docMenu">
                            <span><i class="bi bi-file-earmark-text me-2"></i><span class="menu-text">เอกสาร</span></span>
                            <i class="bi bi-caret-down-fill arrow"></i>
                        </a>

                        <div class="collapse submenu {{ $isDocPage ? 'show' : '' }}" id="docMenu">
                            @if ($canSee('quotations'))
                                <a href="{{ route('quotations.index') }}"
                                    class="sub-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-ruled me-2"></i>ใบเสนอราคา
                                </a>
                            @endif
                            @if ($canSee('sales_orders'))
                                <a href="{{ route('sales_orders.index') }}"
                                    class="sub-link {{ request()->routeIs('sales_orders.*') ? 'active' : '' }}">
                                    <i class="bi bi-cart-check me-2"></i>ใบสั่งขาย
                                </a>
                            @endif
                            @if ($canSee('delivery_notes'))
                                <a href="{{ route('delivery_notes.index') }}"
                                    class="sub-link {{ request()->routeIs('delivery_notes.*') ? 'active' : '' }}">
                                    <i class="bi bi-truck me-2"></i>ใบส่งของ
                                </a>
                            @endif
                            @if ($canSee('invoices'))
                                <a href="{{ route('invoices.index') }}"
                                    class="sub-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                    <i class="bi bi-receipt-cutoff me-2"></i>ใบแจ้งหนี้
                                </a>
                            @endif
                            @if ($canSee('receipts'))
                                <a href="{{ route('receipts.index') }}"
                                    class="sub-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                                    <i class="bi bi-receipt me-2"></i>ใบเสร็จ
                                </a>
                            @endif
                        </div>
                    </li>
                @endif

                @if ($showUserMenu)
                    <li class="nav-item">
                        <a class="nav-link {{ $isUserPage ? 'active' : '' }}" data-bs-toggle="collapse"
                            data-submenu-toggle href="#userMenu" role="button" title="จัดการข้อมูลบุคคล"
                            aria-expanded="{{ $isUserPage ? 'true' : 'false' }}" aria-controls="userMenu">
                            <span><i class="bi bi-people me-2"></i><span class="menu-text">จัดการข้อมูลบุคคล</span></span>
                            <i class="bi bi-caret-down-fill arrow"></i>
                        </a>

                        <div class="collapse submenu {{ $isUserPage ? 'show' : '' }}" id="userMenu">
                            @if (auth()->check() && auth()->user()->isAdmin())
                                <a href="{{ route('users.index') }}"
                                    class="sub-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="bi bi-person-badge me-2"></i>ผู้ใช้งานระบบ
                                </a>
                            @endif

                            @if ($canSee('drivers'))
                                <a href="{{ route('drivers.index') }}"
                                    class="sub-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                                    <i class="bi bi-person-vcard me-2"></i>พนักงานขับรถ
                                </a>
                            @endif

                            @if ($canSee('customers'))
                                <a href="{{ route('customers.index') }}"
                                    class="sub-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                    <i class="bi bi-person-heart me-2"></i>ลูกค้า
                                </a>
                            @endif
                        </div>
                    </li>
                @endif

            </ul>

            @auth
                <div class="dropdown pb-3 user-block">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"
                        title="{{ Auth::user()->name ?? Auth::user()->email }}">
                        <span>
                            <i class="bi bi-person-circle me-2"></i>
                            <span class="menu-text">{{ Auth::user()->name ?? Auth::user()->email }}</span>
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-dark shadow user-menu">
                        @if (auth()->user()->isAdmin())
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.index') }}">
                                    <i class="bi bi-gear me-2"></i>ตั้งค่า
                                </a>
                            </li>
                        @endif

                        <li>
                            <form method="POST" action="{{ route('logout') }}"
            data-confirm="ต้องการออกจากระบบหรือไม่" data-confirm-title="ยืนยันการออกจากระบบ" data-confirm-ok="ออกจากระบบ" data-confirm-variant="danger">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endauth
        </div>
    </div>

    <div class="main-area">
        <button type="button" class="btn btn-dark mobile-open-btn" data-sidebar-open>
            <i class="bi bi-list"></i> เมนู
        </button>

        <div class="content-area">
            @include('partials.breadcrumb')
            @yield('namepage')
            <hr>

            @if (session('ok') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('ok') ?? session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                </div>
            @endif

            @if ($errors->any() && !$errors->hasBag('updateUser') && !$errors->hasBag('updatePassword'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <strong>กรุณาตรวจสอบข้อมูลอีกครั้ง</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <x-confirm-modal />
    <script src="{{ asset('js/vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/vendor/flatpickr/th.js') }}"></script>
    <script src="{{ asset('js/searchable-select.js') }}"></script>
    <script src="{{ asset('js/date-input.js') }}"></script>
    <script src="{{ asset('js/form-validation.js') }}"></script>
    <script src="{{ asset('js/confirm.js') }}"></script>
    <script>
        (function() {
            var root = document.documentElement;
            var MOBILE_MAX = 767.98;

            function isMobile() {
                return window.innerWidth <= MOBILE_MAX;
            }

            function saveState(collapsed) {
                try {
                    localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
                } catch (e) {
                }
            }

            var toggleBtn = document.getElementById('sidebarToggle');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (isMobile()) {
                        root.classList.remove('sidebar-mobile-open');
                        return;
                    }

                    var collapsed = root.classList.toggle('sidebar-collapsed');
                    saveState(collapsed);
                });
            }
            document.querySelectorAll('[data-sidebar-open]').forEach(function(el) {
                el.addEventListener('click', function() {
                    root.classList.add('sidebar-mobile-open');
                });
            });

            document.querySelectorAll('[data-sidebar-close]').forEach(function(el) {
                el.addEventListener('click', function() {
                    root.classList.remove('sidebar-mobile-open');
                });
            });
            document.querySelectorAll('[data-submenu-toggle]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (!isMobile() && root.classList.contains('sidebar-collapsed')) {
                        e.preventDefault();
                        e.stopPropagation();

                        root.classList.remove('sidebar-collapsed');
                        saveState(false);
                        var target = document.querySelector(link.getAttribute('href'));
                        if (target) {
                            setTimeout(function() {
                                bootstrap.Collapse.getOrCreateInstance(target).show();
                            }, 260);
                        }
                    }
                });
            });
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    root.classList.remove('sidebar-mobile-open');
                }
            });
        })();
    </script>

</body>

</html>