<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two Family Co., Ltd.</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/action-buttons.css') }}">

    {{--
        อ่านสถานะย่อ/ขยายก่อนหน้าจะ render
        ต้องวางไว้ใน <head> ไม่ใช่ท้าย body ไม่งั้นจะเห็นเมนูกางออกแล้วหุบวูบหนึ่ง (FOUC)
    --}}
    <script>
        (function() {
            try {
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {
                // localStorage ถูกปิด (โหมดส่วนตัว) ให้ใช้ค่าเริ่มต้นคือกางออก
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

            /* ความกว้างสองสถานะ ประกาศเป็นตัวแปรเพื่อให้แก้ที่เดียวแล้วขยับทั้งหน้า */
            --sidebar-w: 280px;
            --sidebar-w-collapsed: 78px;
        }

        /* สถานะย่อ: เปลี่ยนแค่ค่าตัวแปรตัวเดียว ที่เหลือขยับตามเอง */
        html.sidebar-collapsed {
            --sidebar-w: var(--sidebar-w-collapsed);
        }

        body {
            background-color: #f5f7fa;
            overflow-x: hidden;
        }

        /* ==================== โครงหลัก ==================== */

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

        /* ==================== แถวหัว (ชื่อบริษัท + ปุ่มย่อ/ขยาย) ==================== */

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

        /* ==================== แบรนด์ ==================== */

        .brand {
            flex: 1 1 auto;
            min-width: 0;
            /* ให้ข้อความหดได้ ไม่ดันปุ่มตกบรรทัด */
            color: #fff;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 10px;
            white-space: nowrap;
            overflow: hidden;
        }

        /* ชื่อบริษัทไม่ต้องมีพื้นหลังตอนชี้เมาส์ ทับ .sidebar a:hover ด้านล่าง */
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

        /* ---------- ตอนย่อ: เหลือแต่ปุ่ม จัดกลาง ---------- */

        html.sidebar-collapsed .sidebar-head {
            justify-content: center;
            padding: 16px 0 18px;
        }

        html.sidebar-collapsed .brand {
            display: none;
        }

        /* ==================== เมนู ==================== */

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

        /* ---------- ปรับหน้าตาเมนูตอนย่อ ---------- */

        /* ซ่อนข้อความ เหลือแต่ไอคอน */
        html.sidebar-collapsed .menu-text,
        html.sidebar-collapsed .arrow {
            display: none;
        }

        html.sidebar-collapsed .sidebar .nav-link {
            justify-content: center;
            padding: 12px 0;
        }

        /* ตอนย่อ ไอคอนไม่ต้องมีระยะห่างขวาแล้ว ไม่งั้นจะเยื้องออกจากกลาง */
        html.sidebar-collapsed .sidebar .nav-link i.me-2 {
            margin-right: 0 !important;
            font-size: 1.25rem;
        }

        /* ซ่อนเมนูย่อยตอนย่อ กดแล้วจะให้กางไซด์บาร์ออกก่อน (จัดการใน JS) */
        html.sidebar-collapsed .submenu {
            display: none;
        }

        /* ==================== ส่วนผู้ใช้ ==================== */

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

        /* ==================== มือถือ ==================== */
        /*
            บนจอเล็ก การย่อเหลือไอคอนยังกินพื้นที่มากเกินไป
            เปลี่ยนเป็นเลื่อนออกนอกจอแล้วเปิดทับเนื้อหาแทน
        */
        @media (max-width: 767.98px) {
            .sidebar {
                width: 280px;
                transform: translateX(-100%);
            }

            html.sidebar-mobile-open .sidebar {
                transform: translateX(0);
            }

            /* บนมือถือให้กางเต็มเสมอ ไม่ใช้โหมดไอคอน */
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

        /* ปุ่มเปิดเมนูบนมือถือ ซ่อนบนจอใหญ่ */
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

        /* เคารพผู้ใช้ที่ปิดแอนิเมชันในระบบปฏิบัติการ */
        @media (prefers-reduced-motion: reduce) {

            .sidebar,
            .main-area {
                transition: none;
            }
        }
    </style>
</head>

<body>

    {{-- ฉากหลังสีดำตอนเปิดเมนูบนมือถือ กดแล้วปิด --}}
    <div class="mobile-backdrop" data-sidebar-close></div>

    {{-- ==================== SIDEBAR ==================== --}}
    <div class="sidebar">
        <div class="d-flex flex-column px-3 min-vh-100">

            {{-- แถวหัว: ชื่อบริษัทซ้าย ปุ่มย่อ/ขยายขวา --}}
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
                $isProductPage = request()->routeIs('products.*') || request()->routeIs('product_types.*');

                $isTruckPage =
                    request()->routeIs('truck_brands.*') ||
                    request()->routeIs('truck_models.*') ||
                    request()->routeIs('trucks.*');

                $isDocPage =
                    request()->routeIs('quotations.*') ||
                    request()->routeIs('sales-orders.*') ||
                    request()->routeIs('delivery-notes.*') ||
                    request()->routeIs('invoices.*') ||
                    request()->routeIs('receipts.*') ||
                    request()->routeIs('tax-invoices.*');

                $isUserPage =
                    request()->routeIs('users.*') ||
                    request()->routeIs('drivers.*') ||
                    request()->routeIs('customers.*');
            @endphp

            <ul class="nav flex-column mb-auto">

                {{-- แดชบอร์ด --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="แดชบอร์ด">
                        <span><i class="bi bi-speedometer2 me-2"></i><span class="menu-text">แดชบอร์ด</span></span>
                    </a>
                </li>

                {{-- แคมป์งาน --}}
                <li class="nav-item">
                    <a href="{{ route('camps.index') }}"
                        class="nav-link {{ request()->routeIs('camps.*') ? 'active' : '' }}" title="แคมป์งาน">
                        <span><i class="bi bi-geo-alt me-2"></i><span class="menu-text">แคมป์งาน</span></span>
                    </a>
                </li>

                {{-- สินค้า --}}
                <li class="nav-item">
                    <a class="nav-link {{ $isProductPage ? 'active' : '' }}" data-bs-toggle="collapse"
                        data-submenu-toggle href="#productMenu" role="button" title="สินค้า"
                        aria-expanded="{{ $isProductPage ? 'true' : 'false' }}" aria-controls="productMenu">
                        <span><i class="bi bi-box-seam me-2"></i><span class="menu-text">สินค้า</span></span>
                        <i class="bi bi-caret-down-fill arrow"></i>
                    </a>

                    <div class="collapse submenu {{ $isProductPage ? 'show' : '' }}" id="productMenu">
                        <a href="{{ route('product_types.index') }}"
                            class="sub-link {{ request()->routeIs('product_types.*') ? 'active' : '' }}">
                            <i class="bi bi-tags me-2"></i>ประเภทสินค้า
                        </a>
                        <a href="{{ route('products.index') }}"
                            class="sub-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="bi bi-boxes me-2"></i>สินค้าทั้งหมด
                        </a>
                    </div>
                </li>

                {{-- ต้นทุนค่าน้ำมัน --}}
                <li class="nav-item">
                    <a href="{{ route('fuel_records.index') }}"
                        class="nav-link {{ request()->routeIs('fuel_records.*') ? 'active' : '' }}"
                        title="ต้นทุนค่าน้ำมัน">
                        <span><i class="bi bi-fuel-pump me-2"></i><span class="menu-text">ต้นทุนค่าน้ำมัน</span></span>
                    </a>
                </li>

                {{-- จัดการรถบรรทุก --}}
                <li class="nav-item">
                    <a class="nav-link {{ $isTruckPage ? 'active' : '' }}" data-bs-toggle="collapse"
                        data-submenu-toggle href="#truckMenu" role="button" title="จัดการรถบรรทุก"
                        aria-expanded="{{ $isTruckPage ? 'true' : 'false' }}" aria-controls="truckMenu">
                        <span><i class="bi bi-truck me-2"></i><span class="menu-text">จัดการรถบรรทุก</span></span>
                        <i class="bi bi-caret-down-fill arrow"></i>
                    </a>

                    <div class="collapse submenu {{ $isTruckPage ? 'show' : '' }}" id="truckMenu">
                        <a href="{{ route('truck_brands.index') }}"
                            class="sub-link {{ request()->routeIs('truck_brands.*') ? 'active' : '' }}">
                            <i class="bi bi-award me-2"></i>ยี่ห้อรถบรรทุก
                        </a>
                        <a href="{{ route('truck_models.index') }}"
                            class="sub-link {{ request()->routeIs('truck_models.*') ? 'active' : '' }}">
                            <i class="bi bi-card-list me-2"></i>รุ่นรถบรรทุก
                        </a>
                        <a href="{{ route('trucks.index') }}"
                            class="sub-link {{ request()->routeIs('trucks.*') ? 'active' : '' }}">
                            <i class="bi bi-truck-front me-2"></i>รถบรรทุกในบริษัท
                        </a>
                    </div>
                </li>

                {{-- เอกสาร --}}
                <li class="nav-item">
                    <a class="nav-link {{ $isDocPage ? 'active' : '' }}" data-bs-toggle="collapse" data-submenu-toggle
                        href="#docMenu" role="button" title="เอกสาร"
                        aria-expanded="{{ $isDocPage ? 'true' : 'false' }}" aria-controls="docMenu">
                        <span><i class="bi bi-file-earmark-text me-2"></i><span class="menu-text">เอกสาร</span></span>
                        <i class="bi bi-caret-down-fill arrow"></i>
                    </a>

                    <div class="collapse submenu {{ $isDocPage ? 'show' : '' }}" id="docMenu">
                        <a href="{{ route('quotations.index') }}"
                            class="sub-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                            <i class="bi bi-file-earmark-ruled me-2"></i>ใบเสนอราคา
                        </a>
                        <a href="{{ route('sales-orders.index') }}"
                            class="sub-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}">
                            <i class="bi bi-cart-check me-2"></i>ใบสั่งขาย
                        </a>
                        <a href="{{ route('delivery-notes.index') }}"
                            class="sub-link {{ request()->routeIs('delivery-notes.*') ? 'active' : '' }}">
                            <i class="bi bi-truck me-2"></i>ใบส่งของ
                        </a>
                        <a href="{{ route('invoices.index') }}"
                            class="sub-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt-cutoff me-2"></i>ใบแจ้งหนี้
                        </a>
                        <a href="{{ route('receipts.index') }}"
                            class="sub-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt me-2"></i>ใบเสร็จ
                        </a>
                    </div>
                </li>

                {{-- จัดการข้อมูลบุคคล --}}
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

                        <a href="{{ route('drivers.index') }}"
                            class="sub-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                            <i class="bi bi-person-vcard me-2"></i>พนักงานขับรถ
                        </a>

                        <a href="{{ route('customers.index') }}"
                            class="sub-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i class="bi bi-person-heart me-2"></i>ลูกค้า
                        </a>
                    </div>
                </li>

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
                            <form method="POST" action="{{ route('logout') }}">
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

    {{-- ==================== เนื้อหา ==================== --}}
    <div class="main-area">

        {{-- ปุ่มเปิดเมนูบนมือถือ --}}
        <button type="button" class="btn btn-dark mobile-open-btn" data-sidebar-open>
            <i class="bi bi-list"></i> เมนู
        </button>

        <div class="content-area">
            @yield('namepage')
            <hr>

            {{-- ===== แจ้งเตือนผลการทำรายการ ===== --}}
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

            {{-- แจ้งเตือนข้อผิดพลาดจากการกรอกฟอร์ม --}}
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
            {{-- ===== จบส่วนแจ้งเตือน ===== --}}

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- ระบบยืนยันการทำรายการ --}}
    <x-confirm-modal />
    <script src="{{ asset('js/confirm.js') }}"></script>

    {{-- ==================== สคริปต์ควบคุมไซด์บาร์ ==================== --}}
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
                    // ถ้าเซฟไม่ได้ก็ยังใช้งานได้ปกติ แค่ไม่จำสถานะข้ามหน้า
                }
            }

            // ---------- ปุ่มย่อ/ขยาย ----------
            var toggleBtn = document.getElementById('sidebarToggle');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    // บนมือถือ ปุ่มนี้ทำหน้าที่ "ปิดเมนู" แทนการย่อ
                    if (isMobile()) {
                        root.classList.remove('sidebar-mobile-open');
                        return;
                    }

                    var collapsed = root.classList.toggle('sidebar-collapsed');
                    saveState(collapsed);
                });
            }

            // ---------- เปิด/ปิดเมนูบนมือถือ ----------
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

            // ---------- กดเมนูที่มีเมนูย่อยตอนไซด์บาร์ย่ออยู่ ----------
            /*
               ตอนย่อเหลือแต่ไอคอน เมนูย่อยถูกซ่อนด้วย CSS
               ถ้าปล่อยให้ Bootstrap เปิด collapse ตามปกติ ผู้ใช้จะกดแล้วเหมือนไม่มีอะไรเกิดขึ้น
               จึงดักไว้ให้กางไซด์บาร์ออกก่อน แล้วค่อยเปิดเมนูย่อย
            */
            document.querySelectorAll('[data-submenu-toggle]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (!isMobile() && root.classList.contains('sidebar-collapsed')) {
                        e.preventDefault();
                        e.stopPropagation();

                        root.classList.remove('sidebar-collapsed');
                        saveState(false);

                        // รอให้ไซด์บาร์กางเสร็จก่อนค่อยเปิดเมนูย่อย ไม่งั้นความสูงคำนวณผิด
                        var target = document.querySelector(link.getAttribute('href'));
                        if (target) {
                            setTimeout(function() {
                                bootstrap.Collapse.getOrCreateInstance(target).show();
                            }, 260);
                        }
                    }
                });
            });

            // ---------- ปิดเมนูมือถืออัตโนมัติเมื่อขยายจอ ----------
            window.addEventListener('resize', function() {
                if (!isMobile()) {
                    root.classList.remove('sidebar-mobile-open');
                }
            });
        })();
    </script>

</body>

</html>
