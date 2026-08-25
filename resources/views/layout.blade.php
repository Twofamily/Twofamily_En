<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two Family Co., Ltd.</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: linear-gradient(180deg, #2a2a2a 0%, #141414 100%);
            --sidebar-text: #e5e7eb;
            --sidebar-hover: rgba(255, 255, 255, 0.12);
            --active-bg: #f8fafc;
            --active-text: #141414;
        }

        body {
            background-color: #f5f7fa;
        }

        .sidebar {
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            overflow-y: auto;
        }

        .brand {
            padding: 20px 16px 24px;
            border-radius: 14px;
            color: #fff;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
        }

        .brand-line-1 {
            display: block;
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .brand-line-2 {
            display: block;
            font-size: 1.15rem;
            font-weight: 400;
            opacity: .8;
            line-height: 1.3;
            margin-top: 6px;
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
        }

        .sub-link.active {
            background-color: rgba(255, 255, 255, 0.95);
            color: var(--active-text);
            font-weight: 600;
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

        .user-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, .2);
        }

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

            <div class="col-auto col-md-3 col-xl-2 px-0 position-fixed vh-100 sidebar overflow-auto">
                <div class="d-flex flex-column px-3 pt-3 min-vh-100">

                    <a href="{{ route('dashboard') }}" class="brand">
                        <span class="brand-line-1">บริษัททูแฟมิลี่</span>
                        <span class="brand-line-2">เอ็นจิเนียริ่ง จำกัด</span>
                    </a>

                    @php
                        $isProductPage = request()->routeIs('products.*') || request()->routeIs('product_types.*');

                        $isTruckPage =
                            request()->routeIs('truck_brands.*') ||
                            request()->routeIs('truck_models.*') ||
                            request()->routeIs('trucks.*');

                        $isDocPage =
                            request()->routeIs('quotations.*') ||
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
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <span>แดชบอร์ด</span>
                            </a>
                        </li>

                        {{-- แคมป์งาน --}}
                        <li class="nav-item">
                            <a href="{{ route('camps.index') }}"
                                class="nav-link {{ request()->routeIs('camps.*') ? 'active' : '' }}">
                                <span>แคมป์งาน</span>
                            </a>
                        </li>

                        {{-- สินค้า --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $isProductPage ? 'active' : '' }}" data-bs-toggle="collapse"
                                href="#productMenu" role="button"
                                aria-expanded="{{ $isProductPage ? 'true' : 'false' }}" aria-controls="productMenu">
                                <span>สินค้า</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ $isProductPage ? 'show' : '' }}" id="productMenu">
                                <a href="{{ route('product_types.index') }}"
                                    class="sub-link {{ request()->routeIs('product_types.*') ? 'active' : '' }}">
                                    ประเภทสินค้า
                                </a>
                                <a href="{{ route('products.index') }}"
                                    class="sub-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                                    สินค้าทั้งหมด
                                </a>
                            </div>
                        </li>

                        {{-- ต้นทุนค่าน้ำมัน --}}
                        <li class="nav-item">
                            <a href="{{ route('fuel_records.index') }}"
                                class="nav-link {{ request()->routeIs('fuel_records.*') ? 'active' : '' }}">
                                <span>ต้นทุนค่าน้ำมัน</span>
                            </a>
                        </li>

                        {{-- จัดการรถบรรทุก --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $isTruckPage ? 'active' : '' }}" data-bs-toggle="collapse"
                                href="#truckMenu" role="button" aria-expanded="{{ $isTruckPage ? 'true' : 'false' }}"
                                aria-controls="truckMenu">
                                <span>จัดการรถบรรทุก</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ $isTruckPage ? 'show' : '' }}" id="truckMenu">
                                <a href="{{ route('truck_brands.index') }}"
                                    class="sub-link {{ request()->routeIs('truck_brands.*') ? 'active' : '' }}">
                                    ยี่ห้อรถบรรทุก
                                </a>
                                <a href="{{ route('truck_models.index') }}"
                                    class="sub-link {{ request()->routeIs('truck_models.*') ? 'active' : '' }}">
                                    รุ่นรถบรรทุก
                                </a>
                                <a href="{{ route('trucks.index') }}"
                                    class="sub-link {{ request()->routeIs('trucks.*') ? 'active' : '' }}">
                                    รถบรรทุกในบริษัท
                                </a>
                            </div>
                        </li>

                        {{-- เอกสาร --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $isDocPage ? 'active' : '' }}" data-bs-toggle="collapse"
                                href="#docMenu" role="button" aria-expanded="{{ $isDocPage ? 'true' : 'false' }}"
                                aria-controls="docMenu">
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

                        {{-- จัดการผู้ใช้งาน --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $isUserPage ? 'active' : '' }}" data-bs-toggle="collapse"
                                href="#userMenu" role="button" aria-expanded="{{ $isUserPage ? 'true' : 'false' }}"
                                aria-controls="userMenu">
                                <span>จัดการผู้ใช้งาน</span>
                                <i class="bi bi-caret-down-fill arrow"></i>
                            </a>

                            <div class="collapse submenu {{ $isUserPage ? 'show' : '' }}" id="userMenu">

                                {{-- ยังไม่มี route roles.index — ปลดคอมเมนต์เมื่อสร้าง RoleController แล้ว --}}
                                {{-- <a href="{{ route('roles.index') }}"
                                    class="sub-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                    บทบาท
                                </a> --}}

                                @if (auth()->check() && auth()->user()->isAdmin())
                                    <a href="{{ route('users.index') }}"
                                        class="sub-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                        พนักงานบริษัท
                                    </a>
                                @endif

                                <a href="{{ route('drivers.index') }}"
                                    class="sub-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                                    พนักงานขับรถ
                                </a>

                                <a href="{{ route('customers.index') }}"
                                    class="sub-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                    ลูกค้าประจำ
                                </a>
                            </div>
                        </li>

                    </ul>

                    @auth
                        <div class="dropdown pb-3">
                            <a href="#" class="nav-link text-white text-decoration-none dropdown-toggle"
                                data-bs-toggle="dropdown">
                                {{ Auth::user()->name ?? Auth::user()->email }}
                            </a>

                            <ul class="dropdown-menu dropdown-menu-dark shadow user-menu w-100">
                                @if (auth()->user()->isAdmin())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                                            ตั้งค่า
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                @endif

                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            ออกจากระบบ
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- ระบบยืนยันการทำรายการ --}}
    <x-confirm-modal />
    <script src="{{ asset('js/confirm.js') }}"></script>

</body>

</html>
