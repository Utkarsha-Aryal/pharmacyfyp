@php
    $imgPath = app_logo_url();
    $isDashboard = request()->routeIs('admin.dashboard');
    // Keep the active state narrow so one page does not light up many menus.
    $isInventoryMenu = request()->routeIs(
        'admin.category',
        'admin.category.*',
        'admin.unit',
        'admin.unit.*',
        'admin.product',
        'admin.product.*',
        'admin.batch',
        'admin.batch.*',
        'admin.inventory.batches.*',
        'admin.inventory.adjustments.*'
    );
    $isPurchaseMenu = request()->routeIs(
        'admin.supplier',
        'admin.supplier.*',
        'admin.purchase',
        'admin.purchase.*',
        'admin.purchase-orders.*',
        'admin.purchase-returns.*'
    );
    $isPartyMenu = request()->routeIs('admin.customers.*');
    $isSalesMenu = request()->routeIs('admin.sales.*');
    $isFinanceMenu = request()->routeIs('admin.finance.*', 'admin.expenses.*', 'admin.payments.*');
    $isReportMenu = request()->routeIs('admin.report.*');
    $isUsersMenu = request()->routeIs('admin.user.*');
    $isRoleMenu = request()->routeIs('admin.role-permission.*');
    $isSettingsMenu = request()->routeIs('admin.settings.*');
@endphp

<aside class="app-sidebar sticky" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="header-logo">
            <img src="{{ $imgPath }}" class="rounded-circle website_logo" alt="Pharmacy Logo" width="37" height="40">
        </a>
    </div>

    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>

            <ul class="main-menu">
                <li class="slide__category"><span class="category-name">Main Menu</span></li>

                @can('dashboard.view')
                    <li class="slide {{ $isDashboard ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24">
                                <path d="M0 0h24v24H0V0z" fill="none" />
                                <path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3" />
                                <path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z" />
                            </svg>
                            <span class="side-menu__label">Dashboard</span>
                        </a>
                    </li>
                @endcan

                @if (auth()->user()->can('inventory.category') || auth()->user()->can('inventory.unit') || auth()->user()->can('inventory.product') || auth()->user()->can('inventory.batch'))
                    <li class="slide has-sub {{ $isInventoryMenu ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm8.5-1v12H14a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zm-1 0H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h5.5z" />
                            </svg>
                            <span class="side-menu__label">Inventory</span>
                            <i class="fe fe-chevron-right side-menu__angle"></i>
                        </a>
                        <ul class="slide-menu child1">
                            <li class="slide side-menu__label1">
                                <a href="javascript:void(0);">Inventory</a>
                            </li>
                            @can('inventory.category')
                                <li class="slide {{ request()->routeIs('admin.category', 'admin.category.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.category') }}" class="side-menu__item">Category</a>
                                </li>
                            @endcan
                            @can('inventory.unit')
                                <li class="slide {{ request()->routeIs('admin.unit', 'admin.unit.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.unit') }}" class="side-menu__item">Unit</a>
                                </li>
                            @endcan
                            @can('inventory.product')
                                <li class="slide {{ request()->routeIs('admin.product', 'admin.product.*', 'admin.batch', 'admin.batch.*', 'admin.inventory.products.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.product') }}" class="side-menu__item">Product</a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="slide {{ request()->routeIs('admin.inventory.batches.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inventory.batches.index') }}" class="side-menu__item">Batches</a>
                                </li>
                                <li class="slide {{ request()->routeIs('admin.inventory.adjustments.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.inventory.adjustments.index') }}" class="side-menu__item">Stock Adjustment</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (auth()->user()->can('purchase.supplier') || auth()->user()->can('purchase.entry') || auth()->user()->can('purchase.orders') || auth()->user()->hasRole(['admin', 'superadmin']))
                    <li class="slide has-sub {{ $isPurchaseMenu ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1z" />
                            </svg>
                            <span class="side-menu__label">Purchase</span>
                            <i class="fe fe-chevron-right side-menu__angle"></i>
                        </a>
                        <ul class="slide-menu child1">
                            <li class="slide side-menu__label1">
                                <a href="javascript:void(0);">Purchase</a>
                            </li>
                            @can('purchase.supplier')
                                <li class="slide {{ request()->routeIs('admin.supplier', 'admin.supplier.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.supplier') }}" class="side-menu__item">Supplier</a>
                                </li>
                            @endcan
                            @can('purchase.entry')
                                <li class="slide {{ request()->routeIs('admin.purchase', 'admin.purchase.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.purchase') }}" class="side-menu__item">Purchase Bills</a>
                                </li>
                            @endcan
                            @can('purchase.orders')
                                <li class="slide {{ request()->routeIs('admin.purchase-orders.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.purchase-orders.index') }}" class="side-menu__item">Purchase Orders</a>
                                </li>
                            @endcan
                            @if (auth()->user()->hasRole(['admin', 'superadmin']))
                                <li class="slide {{ request()->routeIs('admin.purchase-returns.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.purchase-returns.index') }}" class="side-menu__item">Purchase Returns</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (auth()->user()->can('party.manage'))
                    <li class="slide {{ $isPartyMenu ? 'active' : '' }}">
                        <a href="{{ route('admin.customers.index') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 0a5 5 0 0 0-5 5c0 1.934 1.09 3.62 2.69 4.47C2.36 10.26 0 12.58 0 15h1c0-2.76 3.13-5 7-5s7 2.24 7 5h1c0-2.42-2.36-4.74-5.69-5.53A5 5 0 0 0 8 0m0 1a4 4 0 1 1 0 8 4 4 0 0 1 0-8" />
                            </svg>
                            <span class="side-menu__label">Party Management</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->can('sales.invoice'))
                    <li class="slide {{ $isSalesMenu ? 'active' : '' }}">
                        <a href="{{ route('admin.sales.index') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2 2h12v12H2z" opacity=".2"></path>
                                <path d="M1 1h14v14H1zm1 1v12h12V2z"></path>
                                <path d="M4 4h8v1H4zm0 3h8v1H4zm0 3h5v1H4z"></path>
                            </svg>
                            <span class="side-menu__label">Sales / POS</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->can('expense.manage') || auth()->user()->can('accounting.ledger') || auth()->user()->can('accounting.trial_balance') || auth()->user()->can('accounting.cash_book') || auth()->user()->can('accounting.bank_book'))
                    <li class="slide has-sub {{ $isFinanceMenu ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 0h1v15h15v1H0z"></path>
                                <path d="M3 11h10v1H3zm0-3h7v1H3zm0-3h10v1H3z"></path>
                            </svg>
                            <span class="side-menu__label">Accounting &amp; Finance</span>
                            <i class="fe fe-chevron-right side-menu__angle"></i>
                        </a>
                        <ul class="slide-menu child1">
                            <li class="slide side-menu__label1">
                                <a href="javascript:void(0);">Accounting</a>
                            </li>
                            @can('accounting.ledger')
                                <li class="slide {{ request()->routeIs('admin.finance.ledger') ? 'active' : '' }}">
                                    <a href="{{ route('admin.finance.ledger') }}" class="side-menu__item">Ledger</a>
                                </li>
                                <li class="slide {{ request()->routeIs('admin.finance.account-tree') ? 'active' : '' }}">
                                    <a href="{{ route('admin.finance.account-tree') }}" class="side-menu__item">Account Tree</a>
                                </li>
                            @endcan
                            @can('accounting.trial_balance')
                                <li class="slide {{ request()->routeIs('admin.finance.trial-balance') ? 'active' : '' }}">
                                    <a href="{{ route('admin.finance.trial-balance') }}" class="side-menu__item">Trial Balance</a>
                                </li>
                            @endcan
                            @can('accounting.cash_book')
                                <li class="slide {{ request()->routeIs('admin.finance.cash-book') ? 'active' : '' }}">
                                    <a href="{{ route('admin.finance.cash-book') }}" class="side-menu__item">Cash Book</a>
                                </li>
                            @endcan
                            @can('accounting.bank_book')
                                <li class="slide {{ request()->routeIs('admin.finance.bank-book') ? 'active' : '' }}">
                                    <a href="{{ route('admin.finance.bank-book') }}" class="side-menu__item">Bank Book</a>
                                </li>
                            @endcan
                            @can('accounting.ledger')
                                <li class="slide {{ request()->routeIs('admin.payments.index', 'admin.payments.show') ? 'active' : '' }}">
                                    <a href="{{ route('admin.payments.index') }}" class="side-menu__item">Payments</a>
                                </li>
                            @endcan
                            @can('expense.manage')
                                <li class="slide {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                                    <a href="{{ route('admin.expenses.index') }}" class="side-menu__item">Expenses</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (auth()->user()->can('report.low_stock') || auth()->user()->can('report.expiry'))
                    <li class="slide has-sub {{ $isReportMenu ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 6a.5.5 0 0 1-.772.06L7.06 7.06l-3.147 4.196a.5.5 0 1 1-.8-.6l3.5-4.667a.5.5 0 0 1 .74-.038l2.57 2.57 4.19-5.587a.5.5 0 0 1 .704-.07" />
                            </svg>
                            <span class="side-menu__label">Reports</span>
                            <i class="fe fe-chevron-right side-menu__angle"></i>
                        </a>
                        <ul class="slide-menu child1">
                            <li class="slide side-menu__label1">
                                <a href="javascript:void(0);">Reports</a>
                            </li>
                            @can('report.low_stock')
                                <li class="slide {{ request()->routeIs('admin.report.lowstock') ? 'active' : '' }}">
                                    <a href="{{ route('admin.report.lowstock') }}" class="side-menu__item">Low Stock</a>
                                </li>
                            @endcan
                            @can('report.expiry')
                                <li class="slide {{ request()->routeIs('admin.report.expiry') ? 'active' : '' }}">
                                    <a href="{{ route('admin.report.expiry') }}" class="side-menu__item">Expiry Alert</a>
                                </li>
                            @endcan
                            @can('report.purchases')
                                <li class="slide {{ request()->routeIs('admin.report.purchases') ? 'active' : '' }}">
                                    <a href="{{ route('admin.report.purchases') }}" class="side-menu__item">Purchase History</a>
                                </li>
                            @endcan
                            @can('report.suppliers')
                                <li class="slide {{ request()->routeIs('admin.report.suppliers') ? 'active' : '' }}">
                                    <a href="{{ route('admin.report.suppliers') }}" class="side-menu__item">Supplier Performance</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                @if (is_admin_user())
                    <li class="slide {{ $isUsersMenu ? 'active' : '' }}">
                        <a href="{{ route('admin.user.index') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M13 7a3 3 0 1 0-2.999-3A3 3 0 0 0 13 7M3 8a3 3 0 1 0-3-3 3 3 0 0 0 3 3m10 1c-1.33 0-4 .67-4 2v1h7v-1c0-1.33-2.67-2-4-2M3 9c-1.33 0-4 .67-4 2v1h7v-1c0-1.33-2.67-2-4-2m5.216 2A2.24 2.24 0 0 0 8 12v1h-.001v-1c0-.345.076-.678.217-1" />
                            </svg>
                            <span class="side-menu__label">Users</span>
                        </a>
                    </li>

                    <li class="slide {{ $isRoleMenu ? 'active' : '' }}">
                        <a href="{{ route('admin.role-permission.index') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 0c.69 0 1.35.28 1.84.77l4.39 4.39c.49.49.77 1.15.77 1.84v2.99c0 3.64-2.42 5.84-6.42 6.95a2.1 2.1 0 0 1-1.16 0C3.42 15.83 1 13.63 1 9.99V7c0-.69.28-1.35.77-1.84L6.16.77C6.65.28 7.31 0 8 0m0 2.1L3.1 7v2.99c0 2.66 1.63 4.22 4.9 5.17 3.27-.95 4.9-2.5 4.9-5.17V7zm0 2.3a1.9 1.9 0 1 0 0 3.8 1.9 1.9 0 0 0 0-3.8m0 5.1c-1.76 0-3.2.96-3.2 2.13 0 .2.16.37.37.37h5.66a.37.37 0 0 0 .37-.37c0-1.17-1.44-2.13-3.2-2.13" />
                            </svg>
                            <span class="side-menu__label">Role Access</span>
                        </a>
                    </li>

                    <li class="slide {{ $isSettingsMenu ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.index') }}" class="side-menu__item">
                            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
                            </svg>
                            <span class="side-menu__label">Settings</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="slide-right" id="slide-right">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg>
            </div>
        </nav>
    </div>
</aside>
