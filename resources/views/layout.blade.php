<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two Family Co., Ltd.</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ===============================
       THEME VARIABLES
    =============================== */
        :root {
            --sidebar-bg: linear-gradient(180deg, #2b4466 0%, #223654 100%);
            --sidebar-text: #e5e7eb;
            --sidebar-hover: rgba(255, 255, 255, 0.12);
            --active-bg: #f8fafc;
            --active-text: #223654;
        }

        body {
            background-color: #f5f7fa;
        }

        /* ===============================
       SIDEBAR BASE
    =============================== */
        .sidebar {
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
        }

        .brand {
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
        }

        /* ===============================
       LINK BASE (nav + sub)
    =============================== */
        .sidebar a {
            transition: all .25s ease;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        /* ===============================
       MAIN NAV (PILL)
    =============================== */
        .sidebar .nav-link {
            border-radius: 18px;
            padding: 14px 18px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: inherit;
        }

        .sidebar .nav-link.active {
            background-color: var(--active-bg);
            color: var(--active-text);
            font-weight: 700;
        }

        /* ===============================
       ARROW ICON
    =============================== */
        .arrow {
            font-size: .8rem;
            transition: transform .25s ease;
        }

        [aria-expanded="true"] .arrow {
            transform: rotate(180deg);
        }

        /* ===============================
       SUB MENU
    =============================== */
        .submenu {
            padding-left: 12px;
            margin-top: 4px;
        }

        .sub-link {
            display: block;
            border-radius: 14px;
            padding: 12px 16px;
            font-size: .95rem;
            margin-bottom: 6px;
            color: inherit;
        }

        .sub-link.active {
            background-color: rgba(255, 255, 255, 0.95);
            color: var(--active-text);
            font-weight: 600;
        }

        /* ===============================
       USER DROPDOWN
    =============================== */
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

        .user-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, .2);
        }

        /* ===============================
       CONTENT
    =============================== */
        .content-area {
            background-color: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="overflow-x-hidden">
    <div class="container-fluid p-0">
        <div class="row m-0 flex-nowrap">

            <div class="col-auto col-md-3 col-xl-2 px-0 position-fixed vh-100 sidebar">
                <div class="d-flex flex-column px-3 pt-3 min-vh-100">

                    <a href="{{ route('dashboard') }}" class="brand fs-2">
                        TWO FAMILY CO., LTD.
                    </a>

                    <ul class="nav flex-column mb-auto">

                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <span>หน้าหลัก</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('customers.index') }}"
                                class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                <span>ลูกค้า</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('camps.index') }}"
                                class="nav-link {{ request()->routeIs('camps.*') ? 'active' : '' }}">
                                <span>แคมป์งาน</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('products.*') || request()->routeIs('product_types.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" href="#productMenu" role="button"
                                aria-expanded="{{ request()->routeIs('products.*') || request()->routeIs('product_types.*') ? 'true' : 'false' }}"
                                aria-controls="productMenu">

                                <span>สินค้า</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ request()->routeIs('products.*') || request()->routeIs('product_types.*') ? 'show' : '' }}"
                                id="productMenu" data-bs-parent="#sidebarMenu">

                                <a href="{{ route('products.index') }}"
                                    class="sub-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                    สินค้าทั้งหมด
                                </a>

                                <a href="{{ route('product_types.index') }}"
                                    class="sub-link {{ request()->routeIs('product_types.*') ? 'active' : '' }}">
                                    ประเภทสินค้า
                                </a>

                            </div>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('truck_brands.*') || request()->routeIs('truck_models.*') ? 'active' : '' }}"
                                data-bs-toggle="collapse" href="#truckMenu" role="button"
                                aria-expanded="{{ request()->routeIs('truck_brands.*') || request()->routeIs('truck_models.*') ? 'true' : 'false' }}"
                                aria-controls="truckMenu">
                                <span>จัดการข้อมูลรถบรรทุก</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ request()->routeIs('truck_brands.*') || request()->routeIs('truck_models.*') ? 'show' : '' }}"
                                id="truckMenu" data-bs-parent="#sidebarMenu">

                                <a href="{{ route('truck_brands.index') }}"
                                    class="sub-link {{ request()->routeIs('truck_brands.*') ? 'active' : '' }}">
                                    ยี่ห้อรถบรรทุก
                                </a>

                                <a href="{{ route('truck_models.index') }}"
                                    class="sub-link {{ request()->routeIs('truck_models.*') ? 'active' : '' }}">
                                    รุ่นรถบรรทุก
                                </a>

                            </div>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('trucks.index') }}"
                                class="nav-link {{ request()->routeIs('trucks.*') ? 'active' : '' }}">
                                <span>รถบรรทุก</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('drivers.index') }}"
                                class="nav-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                                <span>พนักงานขับรถ</span>
                            </a>
                        </li>

                        {{-- <li class="nav-item">
                            <a href="{{ route('transport-jobs.index') }}"
                                class="nav-link {{ request()->routeIs('transport-jobs.*') ? 'active' : '' }}">
                                <span>แผนงานขนส่ง</span>
                            </a>
                        </li> --}}

                        <li class="nav-item">
                            <a href="{{ route('fuel_records.index') }}"
                                class="nav-link {{ request()->routeIs('fuel_records.*') ? 'active' : '' }}">
                                <span>คำนวณค่าน้ำมัน</span>
                            </a>
                        </li>

                        @php
                            $isDocPage =
                                request()->routeIs('quotations.*') ||
                                request()->routeIs('invoices.*') ||
                                request()->routeIs('receipts.*') ||
                                request()->routeIs('tax-invoices.*');
                        @endphp

                        <li class="nav-item">

                            <a class="nav-link {{ $isDocPage ? 'active' : '' }}" data-bs-toggle="collapse"
                                href="#docMenu" role="button" aria-expanded="{{ $isDocPage ? 'true' : 'false' }}">
                                <span>เอกสาร</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ $isDocPage ? 'show' : '' }}" id="docMenu">

                                <a href="{{ route('quotations.index') }}"
                                    class="sub-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                                    ใบเสนอราคา
                                </a>

                                <a href="{{ route('invoices.index') }}"
                                    class="sub-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                    ใบแจ้งหนี้
                                </a>
                                <a href="{{ route('receipts.index') }}"
                                    class="sub-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                                    ใบเสร็จ
                                </a>
                            </div>
                        </li>

                    </ul>

                    @auth
                        <div class="dropdown pb-3">
                            <a href="#" class="text-white text-decoration-none dropdown-toggle"
                                data-bs-toggle="dropdown">
                                {{ Auth::user()->name ?? Auth::user()->email }}
                            </a>

                            <ul class="dropdown-menu dropdown-menu-dark shadow user-menu">
                                {{-- <li>
                                    <a class="dropdown-item" href="{{ route('profile.show') }}">
                                        จัดการบัญชีผู้ใช้
                                    </a>
                                </li> --}}

                                {{-- เมนูสำหรับผู้ดูแลระบบเท่านั้น --}}
                                @if (auth()->user()->isAdmin())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                                            ตั้งค่า
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('users.index') }}">
                                            จัดการผู้ใช้งาน
                                        </a>
                                    </li>
                                @endif

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            Sign out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>

            <div class="col-md-9 col-xl-10 offset-md-3 offset-xl-2 py-4 vh-100 overflow-auto">
                <div class="content-area">
                    @yield('namepage')
                    <hr>
                    @yield('content')
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
