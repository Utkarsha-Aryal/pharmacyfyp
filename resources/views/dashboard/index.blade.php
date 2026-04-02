@extends('layouts.main')

@section('title')
    Dashboard
@endsection

@section('main-content')
    @php
        // keep the dashboard focused on the parts this user can actually work on
        $currentUser = auth()->user();
        $canInventoryView = $currentUser?->can('inventory.view') || $currentUser?->can('inventory.product') || $currentUser?->can('inventory.batch') || $currentUser?->can('inventory.adjustment');
        $canPurchaseView = $currentUser?->can('purchase.orders') || $currentUser?->can('purchase.entry') || $currentUser?->can('purchase.supplier');
        $canSalesView = $currentUser?->can('sales.invoice') || $currentUser?->can('sales.payment') || $currentUser?->can('sales.return');
        $canReportView = $currentUser?->can('report.low_stock') || $currentUser?->can('report.expiry') || $currentUser?->can('report.purchases') || $currentUser?->can('report.suppliers');
        $canUserManage = $currentUser?->can('user.manage');
        $showAnalyticsTab = $canInventoryView || $canPurchaseView || $canSalesView;
        $showAlertsTab = $canInventoryView || $canReportView;
    @endphp

    <div class="dashboard-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Dashboard</h5>
                <p class="mb-0 text-muted">Overview of stock, purchases, alerts and quick admin actions.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                {{-- role-aware actions: only show buttons the current user can use --}}
                @if ($canPurchaseView)
                    <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Order
                    </a>
                    <a href="{{ route('admin.export.purchase-orders') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-excel"></i> Purchase Excel
                    </a>
                @elseif ($canSalesView)
                    <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Create Invoice
                    </a>
                    <a href="{{ route('admin.export.sales-invoices') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-excel"></i> Sales Excel
                    </a>
                @elseif ($canInventoryView)
                    <a href="{{ route('admin.product') }}" class="btn btn-primary">
                        <i class="fa-solid fa-capsules"></i> Open Products
                    </a>
                    <a href="{{ route('admin.export.inventory-products') }}" class="btn btn-excel">
                        <i class="fa-solid fa-file-excel"></i> Inventory Excel
                    </a>
                @elseif ($canUserManage)
                    <a href="{{ route('admin.user.index') }}" class="btn btn-primary">
                        <i class="fa fa-users"></i> Users
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-primary">
                        <i class="fa fa-gear"></i> Settings
                    </a>
                @endif
            </div>
        </div>

        <div class="card custom-card dashboard-hero-card mb-4">
            <div class="card-body">
                <div class="row align-items-center g-4">
                    <div class="col-xl-7">
                        {{-- role-aware hero card, keep it short and clean --}}
                        <span class="dashboard-hero-badge">Overview</span>
                        <h3 class="dashboard-hero-title">{{ setting('app_name', 'Pharmacy Management System') }}</h3>
                        <p class="dashboard-hero-text">
                            Track the parts of the system you are allowed to manage, without extra clutter.
                        </p>
                        <div class="dashboard-hero-actions">
                            @if ($canInventoryView)
                                <a href="{{ route('admin.product') }}" class="btn btn-light btn-sm">Open Products</a>
                                <a href="{{ route('admin.inventory.batches.index') }}" class="btn btn-outline-light btn-sm">Batches</a>
                            @endif
                            @if ($canPurchaseView)
                                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-outline-light btn-sm">Purchase Orders</a>
                            @endif
                            @if ($canSalesView)
                                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-light btn-sm">Sales</a>
                            @endif
                            @if ($canReportView || $canInventoryView)
                                <a href="{{ route('admin.report.lowstock') }}" class="btn btn-outline-light btn-sm">Low Stock</a>
                                <a href="{{ route('admin.report.expiry', ['window' => '3m']) }}" class="btn btn-outline-light btn-sm">Expiry</a>
                            @endif
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="dashboard-hero-stat">
                            @if ($canPurchaseView)
                                <span>This Month's Purchase Value</span>
                                <strong>{{ money_value($thisMonthPurchaseValue) }}</strong>
                                <small>Received purchase value for {{ now()->format('F Y') }} only.</small>
                            @elseif ($canSalesView)
                                <span>This Month's Sales</span>
                                <strong>{{ money_value($thisMonthSalesValue) }}</strong>
                                <small>Confirmed invoice value for {{ now()->format('F Y') }}.</small>
                            @elseif ($canInventoryView)
                                <span>Total Stock Qty</span>
                                <strong>{{ $totalStock }}</strong>
                                <small>Only the stock data this role needs to watch.</small>
                            @elseif ($canUserManage)
                                <span>System Users</span>
                                <strong>{{ $totalUsers }}</strong>
                                <small>Users who can enter the </small>
                            @else
                                <span>Role View</span>
                                <strong>Limited</strong>
                                <small>This dashboard is trimmed for your account.</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            @if ($canInventoryView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Total Categories</p>
                                <span class="dashboard-mini-icon dashboard-icon-blue">
                                    <i class="fa-solid fa-list"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalCategory }}</div>
                            <p class="dashboard-mini-note">Medicine groups in system.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Total Products</p>
                                <span class="dashboard-mini-icon dashboard-icon-green">
                                    <i class="fa-solid fa-capsules"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalProducts }}</div>
                            <p class="dashboard-mini-note">Product master records.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Total Batches</p>
                                <span class="dashboard-mini-icon dashboard-icon-red">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalBatches }}</div>
                            <p class="dashboard-mini-note">Batch rows for stock.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canPurchaseView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Total Suppliers</p>
                                <span class="dashboard-mini-icon dashboard-icon-orange">
                                    <i class="fa-solid fa-truck-field"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalSuppliers }}</div>
                            <p class="dashboard-mini-note">Supplier records active.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canSalesView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Today's Sales</p>
                                <span class="dashboard-mini-icon dashboard-icon-info">
                                    <i class="fa-solid fa-cash-register"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ money_value($todaySalesValue) }}</div>
                            <p class="dashboard-mini-note">Confirmed invoice value for today.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row g-3 mb-4">
            @if ($canPurchaseView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">This Month Purchase Value</p>
                                <span class="dashboard-mini-icon dashboard-icon-info">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ money_value($thisMonthPurchaseValue) }}</div>
                            <p class="dashboard-mini-note">Received purchase value this month.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">All Time Purchase Value</p>
                                <span class="dashboard-mini-icon dashboard-icon-purple">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ money_value($totalPurchaseValue) }}</div>
                            <p class="dashboard-mini-note">All purchase orders together.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canInventoryView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Total Stock Qty</p>
                                <span class="dashboard-mini-icon dashboard-icon-green">
                                    <i class="fa-solid fa-warehouse"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalStock }}</div>
                            <p class="dashboard-mini-note">Active batch quantity.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canUserManage)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">System Users</p>
                                <span class="dashboard-mini-icon dashboard-icon-orange">
                                    <i class="fa-solid fa-user-shield"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $totalUsers }}</div>
                            <p class="dashboard-mini-note">Admin, staff and procurement.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canSalesView)
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">This Month Sales</p>
                                <span class="dashboard-mini-icon dashboard-icon-green">
                                    <i class="fa-solid fa-chart-line"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ money_value($thisMonthSalesValue) }}</div>
                            <p class="dashboard-mini-note">Invoice value for this month.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Outstanding Receivables</p>
                                <span class="dashboard-mini-icon dashboard-icon-orange">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ money_value($outstandingReceivables) }}</div>
                            <p class="dashboard-mini-note">Still pending from customers.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card dashboard-mini-card">
                        <div class="card-body">
                            <div class="dashboard-mini-head">
                                <p class="dashboard-mini-title">Credit Sales</p>
                                <span class="dashboard-mini-icon dashboard-icon-purple">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </span>
                            </div>
                            <div class="dashboard-mini-value">{{ $creditSalesCount }}</div>
                            <p class="dashboard-mini-note">Confirmed invoices created on credit.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card custom-card dashboard-tab-card">
            <div class="card-body">
                <ul class="nav nav-tabs dashboard-tabs" id="dashboardTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dashboard-overview" type="button" role="tab">
                            Overview
                        </button>
                    </li>
                    @if ($showAnalyticsTab)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dashboard-analytics" type="button" role="tab">
                                Analytics
                            </button>
                        </li>
                    @endif
                    @if ($showAlertsTab)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dashboard-alerts" type="button" role="tab">
                                Alerts
                            </button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content pt-4">
                    <div class="tab-pane fade show active" id="dashboard-overview" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-xl-5">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header">
                                        <div class="card-title">Quick Summary</div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0 dashboard-note-list">
                                            @if ($canInventoryView)
                                                <li>
                                                    <strong>Total Stock Qty: {{ $totalStock }}</strong>
                                                    <span>Current quantity available in active batches.</span>
                                                </li>
                                            @endif
                                            @if ($canReportView || $canInventoryView)
                                                <li>
                                                    <strong>Low Stock Items: {{ $lowStockCount }}</strong>
                                                    <span>Products below or equal to reorder level.</span>
                                                </li>
                                                <li>
                                                    <strong>Expiring Soon: {{ $expiringThreeMonthsCount }}</strong>
                                                    <span>Batches expiring inside the next 3 months.</span>
                                                </li>
                                                <li>
                                                    <strong>Expired Batches: {{ $expiredBatchesCount }}</strong>
                                                    <span>Batches that already crossed expiry date.</span>
                                                </li>
                                            @endif
                                            @if ($canPurchaseView)
                                                <li>
                                                    <strong>Pending POs: {{ $pendingPurchaseOrdersCount }}</strong>
                                                    <span>Purchase orders waiting for approval.</span>
                                                </li>
                                                <li>
                                                    <strong>Outstanding Payables: {{ money_value($outstandingPayables) }}</strong>
                                                    <span>Supplier balance still unpaid.</span>
                                                </li>
                                            @endif
                                            @if ($canSalesView)
                                                <li>
                                                    <strong>All Time Sales: {{ money_value($allTimeSalesValue) }}</strong>
                                                    <span>Total confirmed invoice value in the system.</span>
                                                </li>
                                                <li>
                                                    <strong>Outstanding Receivables: {{ money_value($outstandingReceivables) }}</strong>
                                                    <span>Customer balance still pending.</span>
                                                </li>
                                            @endif
                                        </ul>
                                        <div class="dashboard-link-grid">
                                            @if ($canInventoryView)
                                                <a href="{{ route('admin.product') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-capsules me-1"></i>Products</a>
                                                <a href="{{ route('admin.inventory.batches.index') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-boxes-stacked me-1"></i>Batches</a>
                                            @endif
                                            @if ($canPurchaseView)
                                                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-invoice me-1"></i>Purchase Orders</a>
                                            @endif
                                            @if ($canSalesView)
                                                <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-cash-register me-1"></i>Sales</a>
                                            @endif
                                            @if ($canUserManage)
                                                <a href="{{ route('admin.user.index') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-users me-1"></i>Users</a>
                                                <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-gear me-1"></i>Settings</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($canPurchaseView)
                                <div class="col-xl-7">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Top Suppliers</div>
                                            <a href="{{ route('admin.export.supplier-performance') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-excel"></i> Excel
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive dashboard-table-wrap">
                                                <table class="table table-striped summary-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 70px;">S.No</th>
                                                            <th>Supplier</th>
                                                            <th>Bills</th>
                                                            <th>Total Amount</th>
                                                            <th>Outstanding</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($topSuppliers as $index => $supplier)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $supplier->supplier_name }}</td>
                                                                <td>{{ $supplier->total_bill }}</td>
                                                                <td>{{ money_value($supplier->total_amount) }}</td>
                                                                <td>
                                                                    @if ((float) $supplier->outstanding_amount > 0)
                                                                        <span class="text-danger fw-semibold">
                                                                            {{ money_value($supplier->outstanding_amount) }}
                                                                        </span>
                                                                    @else
                                                                        <span class="report-badge report-badge-success">Paid</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="summary-empty">No supplier purchase data yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between align-items-start">
                                            <div>
                                                <div class="card-title mb-2">Recent Purchases</div>
                                                {{-- status pills are kept, but only for the role that can work on purchase flow --}}
                                                <div class="dashboard-status-pill-wrap">
                                                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'pending']) }}" class="dashboard-status-pill dashboard-status-pending">
                                                        Pending Orders <strong>{{ $purchaseStatusCounts['pending'] }}</strong>
                                                    </a>
                                                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'approved']) }}" class="dashboard-status-pill dashboard-status-approved">
                                                        Approved Orders <strong>{{ $purchaseStatusCounts['approved'] }}</strong>
                                                    </a>
                                                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'received']) }}" class="dashboard-status-pill dashboard-status-received">
                                                        Received Orders <strong>{{ $purchaseStatusCounts['received'] }}</strong>
                                                    </a>
                                                </div>
                                            </div>
                                            <a href="{{ route('admin.export.purchase-orders') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-excel"></i> Excel
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive dashboard-table-wrap">
                                                <table class="table table-striped summary-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 70px;">S.No</th>
                                                            <th>Reference</th>
                                                            <th>Supplier</th>
                                                            <th>Status</th>
                                                            <th>Payment</th>
                                                            <th>Date</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($recentPurchases as $index => $purchase)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $purchase->reference }}</td>
                                                                <td>{{ $purchase->supplier?->supplier_name ?? '-' }}</td>
                                                                <td>
                                                                    <span class="report-badge {{ $purchase->status === 'pending' ? 'report-badge-warning' : ($purchase->status === 'approved' ? 'report-badge-info' : 'report-badge-success') }}">{{ $purchase->status_label }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="report-badge {{ $purchase->payment_status === 'unpaid' ? 'report-badge-danger' : ($purchase->payment_status === 'partial' ? 'report-badge-warning' : 'report-badge-success') }}">{{ $purchase->payment_label }}</span>
                                                                </td>
                                                                <td>{{ $purchase->order_date_show }}</td>
                                                                <td>{{ money_value($purchase->total_amount) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="summary-empty">No purchase data available yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($canSalesView)
                                <div class="{{ $canPurchaseView ? 'col-xl-6' : 'col-xl-12' }}">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Recent Sales</div>
                                            <a href="{{ route('admin.export.sales-invoices') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-excel"></i> Excel
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive dashboard-table-wrap">
                                                <table class="table table-striped summary-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 70px;">S.No</th>
                                                            <th>Reference</th>
                                                            <th>Party</th>
                                                            <th>Payment</th>
                                                            <th>Date</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($recentSales as $index => $sale)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $sale->reference }}</td>
                                                                <td>{{ $sale->customer?->name ?? 'Walk In' }}</td>
                                                                <td>
                                                                    <span class="report-badge {{ $sale->payment_status === 'paid' ? 'report-badge-success' : ($sale->payment_status === 'partial' ? 'report-badge-warning' : 'report-badge-danger') }}">
                                                                        {{ $sale->payment_label }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $sale->invoice_date_show }}</td>
                                                                <td>{{ money_value($sale->total_amount) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="summary-empty">No sales data available yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($showAnalyticsTab)
                    <div class="tab-pane fade" id="dashboard-analytics" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-xl-5">
                                <div class="card custom-card dashboard-inner-card analytics-overview-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Overview Bar Chart</div>
                                    </div>
                                    <div class="card-body dashboard-chart-box">
                                        <canvas id="overviewBarChart" data-labels='@json($overviewChart["labels"])' data-values='@json($overviewChart["values"])'></canvas>
                                    </div>
                                </div>
                            </div>

                            @if ($canPurchaseView)
                                <div class="{{ $canInventoryView ? 'col-xl-7' : 'col-xl-12' }}">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Purchase Trend</div>
                                        </div>
                                        <div class="card-body dashboard-chart-box">
                                            <canvas id="purchaseTrendChart" data-labels='@json($purchaseTrendChart["labels"])' data-values='@json($purchaseTrendChart["values"])'></canvas>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($canSalesView)
                                <div class="{{ $canPurchaseView ? 'col-xl-7' : 'col-xl-12' }}">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Sales vs Purchase</div>
                                        </div>
                                        <div class="card-body dashboard-chart-box">
                                            <canvas id="salesPurchaseChart" data-labels='@json($salesPurchaseChart["labels"])' data-sales='@json($salesPurchaseChart["sales"])' data-purchase='@json($salesPurchaseChart["purchase"])'></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="{{ $canPurchaseView || $canInventoryView ? 'col-xl-5' : 'col-xl-12' }}">
                                    <div class="card custom-card dashboard-inner-card analytics-overview-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Top Selling Products</div>
                                        </div>
                                        <div class="card-body dashboard-chart-box">
                                            <canvas id="topSellingProductsChart" data-labels='@json($topSellingProductsChart["labels"])' data-values='@json($topSellingProductsChart["values"])'></canvas>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($canInventoryView)
                                <div class="{{ $canPurchaseView ? 'col-xl-7' : 'col-xl-12' }}">
                                    <div class="card custom-card dashboard-inner-card">
                                        <div class="card-header justify-content-between">
                                            <div class="card-title">Stock by Category</div>
                                        </div>
                                        <div class="card-body dashboard-chart-box dashboard-chart-box-sm">
                                            <canvas id="stockCategoryChart" data-labels='@json($stockCategoryChart["labels"])' data-values='@json($stockCategoryChart["values"])'></canvas>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if ($showAlertsTab)
                    <div class="tab-pane fade" id="dashboard-alerts" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-xl-6">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Low Stock Alert</div>
                                        <a href="{{ route('admin.export.low-stock') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-file-excel"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 70px;">S.No</th>
                                                        <th>Medicine</th>
                                                        <th>Reorder Level</th>
                                                        <th>Current Stock</th>
                                                        <th>Deficit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($lowStockProducts as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item->product_name }}</td>
                                                            <td>{{ $item->reorder_level }}</td>
                                                            <td>
                                                                <span class="report-badge {{ $item->current_stock == 0 ? 'report-badge-danger' : 'report-badge-warning' }}">
                                                                    {{ $item->current_stock }}
                                                                </span>
                                                            </td>
                                                            <td>{{ max(0, (int) $item->reorder_level - (int) $item->current_stock) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="summary-empty">No low stock items right now.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        {{-- change 3: detailed expiry table for next 30 days --}}
                                        <div class="card-title">Expiry Alerts</div>
                                        <a href="{{ route('admin.export.expiry-alert') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-file-excel"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 70px;">S.No</th>
                                                        <th>Medicine</th>
                                                        <th>Batch Number</th>
                                                        <th>Expiry Date</th>
                                                        <th>Days Remaining</th>
                                                        <th>Stock Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($expiryAlerts as $index => $batch)
                                                        <tr class="{{ $batch->alert_row_class }}">
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $batch->product?->display_name ?? $batch->product?->product_name ?? '-' }}</td>
                                                            <td>{{ $batch->batch_number ?? $batch->batch_no ?? '-' }}</td>
                                                            <td>{{ $batch->expiry_show }}</td>
                                                            <td>{{ $batch->days_left }}</td>
                                                            <td>{{ $batch->quantity_available ?? $batch->quantity }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="summary-empty">No batches are expiring inside the next 30 days.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
