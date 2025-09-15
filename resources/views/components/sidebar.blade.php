<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand mb-5">
            <a href="/home"><img src="{{ asset('img/toga-gold-ts.png') }}" width="100"></a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="/home">TOGA</a>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-header">Menu</li>

            {{-- Dashboard: ditampilkan ke semua user yang login --}}
            <li class="{{ Request::is('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('home') }}">
                    <i class="fas fa-fire"></i> <span>Dashboard</span>
                </a>
            </li>

            {{-- ====== ADMIN ONLY ====== --}}
            @if(Auth::check() && Auth::user()->roles === 'admin')
                <li class="{{ Request::is('user*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.index') }}">
                        <i class="fas fa-user"></i> <span>Users</span>
                    </a>
                </li>

{{-- 
                <li class="nav-item dropdown {{ (Request::is('income*') || Request::is('expenses*')) ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-dollar"></i><span>Finance</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ Request::is('income*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('income.index') }}">
                                <i class="fas fa-arrow-down"></i> <span>Uang Masuk</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('expenses*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('expenses.index') }}">
                                <i class="fas fa-arrow-up"></i> <span>Uang Keluar</span>
                            </a>
                        </li>
                    </ul>
                </li> --}}

                {{-- Inventory module removed --}}

                <li class="{{ (Request::is('product') || Request::is('product/*')) ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('product.index') }}">
                        <i class="fas fa-list"></i> <span>Products</span>
                    </a>
                </li>

                {{-- Employees module removed --}}

                {{-- Attendance module removed --}}

                <li class="{{ Request::is('api/docs') ? 'active' : '' }}">
                    <a class="nav-link" href="/api/docs" target="_blank">
                        <i class="fas fa-book"></i> <span>API Docs</span>
                    </a>
                </li>
            @endif
            {{-- ====== END ADMIN ONLY ====== --}}

            {{-- ====== USER ONLY (menu lainnya) ====== --}}
            @if(Auth::check() && Auth::user()->roles === 'user')
                <li class="nav-item dropdown {{ (Request::is('income*') || Request::is('expenses*')) ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown">
                        <i class="fas fa-dollar"></i><span>Finance</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="{{ Request::is('income*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('income.index') }}">
                                <i class="fas fa-arrow-down"></i> <span>Uang Masuk</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('expenses*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('expenses.index') }}">
                                <i class="fas fa-arrow-up"></i> <span>Uang Keluar</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="{{ Request::is('product') || Request::is('product/*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('product.index') }}">
                        <i class="fas fa-shopping-bag"></i> <span>Products</span>
                    </a>
                </li>

                <li class="{{ Request::is('category*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('category.index') }}">
                        <i class="fas fa-cart-shopping"></i> <span>Categories</span>
                    </a>
                </li>

                <li class="{{ Request::is('order*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('order.index') }}">
                        <i class="fas fa-truck-fast"></i> <span>Orders</span>
                    </a>
                </li>

                {{-- Reports dropdown untuk user --}}
                @php($reportsOpen = Request::is('report*') || Request::is('summary*') || Request::is('product_sales*'))
                <li class="nav-item dropdown {{ $reportsOpen ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown {{ $reportsOpen ? 'active' : '' }}" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}">
                        <i class="fas fa-book"></i><span>Reports</span>
                    </a>
                    <ul class="dropdown-menu {{ $reportsOpen ? 'show' : '' }}" style="{{ $reportsOpen ? 'display:block;' : '' }}">
                        <li class="{{ Request::is('report') || Request::is('report/filter') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.index') }}">
                                <i class="fas fa-book-open"></i> <span>Report Order</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('report/by-category*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.byCategory') }}">
                                <i class="fas fa-layer-group"></i> <span>Order by Category</span>
                            </a>
                        </li>
                        {{-- <li class="{{ Request::is('report/detail*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.detail') }}">
                                <i class="fas fa-list"></i> <span>Order Detail</span>
                            </a>
                        </li> --}}
                        {{-- <li class="{{ Request::is('summary*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('summary.index') }}">
                                <i class="fas fa-chart-pie"></i> <span>Summary</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('product_sales*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('product_sales.index') }}">
                                <i class="fas fa-bar-chart"></i> <span>Product Sales</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('report/payments') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.payments') }}">
                                <i class="fas fa-credit-card"></i> <span>Payments</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('report/time') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.time') }}">
                                <i class="fas fa-clock"></i> <span>Time Analysis</span>
                            </a>
                        </li>
                        <li class="{{ Request::is('report/refunds') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('report.refunds') }}">
                                <i class="fas fa-undo"></i> <span>Refunds</span>
                            </a>
                        </li> --}}
                    </ul>
                </li>
            @endif
            {{-- ====== END USER ONLY ====== --}}
        </ul>
    </aside>
</div>
