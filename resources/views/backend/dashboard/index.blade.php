@extends('backend.layouts.main')

@section('title')
    Dashboard
@endsection

@section('main-content')
    <div class="dashboard-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Dashboard</h5>
                <p class="mb-0 text-muted">Overview of stock, purchases, alerts and quick admin actions.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Purchase
                </a>
                <a href="{{ route('admin.export.purchase') }}" class="btn btn-outline-primary">
                    <i class="fa fa-download"></i> Purchase Excel
                </a>
            </div>
        </div>

        <div class="card custom-card dashboard-hero-card mb-4">
            <div class="card-body">
                <div class="row align-items-center g-4">
                    <div class="col-xl-7">
                        {{-- change 1: hero label now reflects full system summary instead of today only --}}
                        <span class="dashboard-hero-badge">System Overview</span>
                        <h3 class="dashboard-hero-title">{{ setting('app_name', 'Pharmacy Management System') }}</h3>
                        <p class="dashboard-hero-text">
                            Track medicine stock, purchase workflow, expiry alerts and supplier payables from one place.
                        </p>
                        <div class="dashboard-hero-actions">
                            <a href="{{ route('admin.product') }}" class="btn btn-light btn-sm">Open Products</a>
                            <a href="{{ route('admin.purchase') }}" class="btn btn-outline-light btn-sm">Open Purchases</a>
                            <a href="{{ route('admin.report.lowstock') }}" class="btn btn-outline-light btn-sm">Low Stock</a>
                            <a href="{{ route('admin.report.expiry') }}" class="btn btn-outline-light btn-sm">Expiry</a>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        {{-- change 2: hero amount is monthly only --}}
                        <div class="dashboard-hero-stat">
                            <span>This Month's Purchase Value</span>
                            <strong>{{ number_format((float) $thisMonthPurchaseValue, 2) }}</strong>
                            <small>Received purchase value for {{ now()->format('F Y') }} only.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Category</p>
                            <span class="dashboard-mini-icon dashboard-icon-blue">
                                <i class="fa-solid fa-list"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalCategory }}</div>
                        <p class="dashboard-mini-note">Medicine groups in system.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Product</p>
                            <span class="dashboard-mini-icon dashboard-icon-green">
                                <i class="fa-solid fa-capsules"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalProducts }}</div>
                        <p class="dashboard-mini-note">Product master records.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Supplier</p>
                            <span class="dashboard-mini-icon dashboard-icon-orange">
                                <i class="fa-solid fa-truck-field"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalSuppliers }}</div>
                        <p class="dashboard-mini-note">Supplier records active.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Batch</p>
                            <span class="dashboard-mini-icon dashboard-icon-red">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalBatches }}</div>
                        <p class="dashboard-mini-note">Batch rows for stock.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Stock Qty</p>
                            <span class="dashboard-mini-icon dashboard-icon-info">
                                <i class="fa-solid fa-warehouse"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalStock }}</div>
                        <p class="dashboard-mini-note">Active batch quantity.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Purchase Value</p>
                            <span class="dashboard-mini-icon dashboard-icon-purple">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ number_format((float) $totalPurchaseValue, 0) }}</div>
                        <p class="dashboard-mini-note">All received purchase value.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card dashboard-tab-card">
            <div class="card-body">
                <ul class="nav nav-tabs dashboard-tabs" id="dashboardTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dashboard-overview" type="button" role="tab">
                            Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dashboard-analytics" type="button" role="tab">
                            Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dashboard-alerts" type="button" role="tab">
                            Alerts
                        </button>
                    </li>
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
                                            <li>
                                                <strong>Total Stock Qty: {{ $totalStock }}</strong>
                                                <span>Current quantity available in active batches.</span>
                                            </li>
                                            <li>
                                                <strong>Low Stock Items: {{ $lowStockCount }}</strong>
                                                <span>Products below or equal to alert quantity.</span>
                                            </li>
                                            <li>
                                                <strong>Expiring Soon: {{ $expiringSoonCount }}</strong>
                                                <span>Batches expiring inside the next 30 days.</span>
                                            </li>
                                            <li>
                                                <strong>System Users: {{ $totalUsers }}</strong>
                                                <span>Admin and staff accounts with backend access.</span>
                                            </li>
                                        </ul>
                                        <div class="dashboard-link-grid">
                                            <a href="{{ route('admin.supplier') }}" class="btn btn-sm btn-outline-primary">Suppliers</a>
                                            <a href="{{ route('admin.user.index') }}" class="btn btn-sm btn-outline-primary">Users</a>
                                            <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-primary">Settings</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-7">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Top Suppliers</div>
                                        <a href="{{ route('admin.export.purchase-supplier-summary') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-download"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Supplier</th>
                                                        <th>Bills</th>
                                                        <th>Total Amount</th>
                                                        <th>Outstanding</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($topSuppliers as $supplier)
                                                        <tr>
                                                            <td>{{ $supplier->supplier_name }}</td>
                                                            <td>{{ $supplier->total_bill }}</td>
                                                            <td>{{ number_format((float) $supplier->total_amount, 2) }}</td>
                                                            <td>
                                                                @if ((float) $supplier->outstanding_amount > 0)
                                                                    <span class="text-danger fw-semibold">
                                                                        {{ number_format((float) $supplier->outstanding_amount, 2) }}
                                                                    </span>
                                                                @else
                                                                    <span class="report-badge report-badge-success">Paid</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="summary-empty">No supplier purchase data yet.</td>
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
                                            {{-- change 4: purchase status summary above recent purchases table --}}
                                            <div class="dashboard-status-pill-wrap">
                                                <a href="{{ route('admin.purchase', ['order_status' => 'pending']) }}" class="dashboard-status-pill dashboard-status-pending">
                                                    Pending Orders <strong>{{ $purchaseStatusCounts['pending'] }}</strong>
                                                </a>
                                                <a href="{{ route('admin.purchase', ['order_status' => 'approved']) }}" class="dashboard-status-pill dashboard-status-approved">
                                                    Approved Orders <strong>{{ $purchaseStatusCounts['approved'] }}</strong>
                                                </a>
                                                <a href="{{ route('admin.purchase', ['order_status' => 'received']) }}" class="dashboard-status-pill dashboard-status-received">
                                                    Received Orders <strong>{{ $purchaseStatusCounts['received'] }}</strong>
                                                </a>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin.export.purchase') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-download"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Reference</th>
                                                        <th>Supplier</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($recentPurchases as $purchase)
                                                        @php
                                                            $statusClass = match ($purchase->order_status) {
                                                                'pending' => 'report-badge-warning',
                                                                'approved' => 'report-badge-info',
                                                                default => 'report-badge-success',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $purchase->reference?->reference_no ?? '-' }}</td>
                                                            <td>{{ $purchase->supplier?->supplier_name ?? '-' }}</td>
                                                            <td>
                                                                <span class="report-badge {{ $statusClass }}">{{ $purchase->order_status_label }}</span>
                                                            </td>
                                                            <td>{{ $purchase->purchase_date_show }}</td>
                                                            <td>{{ number_format((float) $purchase->grand_total, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="summary-empty">No purchase data available yet.</td>
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

                    <div class="tab-pane fade" id="dashboard-analytics" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-xl-7">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Purchase Trend</div>
                                    </div>
                                    <div class="card-body dashboard-chart-box">
                                        <canvas id="purchaseTrendChart" data-labels='@json($purchaseTrendChart["labels"])' data-values='@json($purchaseTrendChart["values"])'></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-5">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Stock by Category</div>
                                    </div>
                                    <div class="card-body dashboard-chart-box dashboard-chart-box-sm">
                                        <canvas id="stockCategoryChart" data-labels='@json($stockCategoryChart["labels"])' data-values='@json($stockCategoryChart["values"])'></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="dashboard-alerts" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-xl-6">
                                <div class="card custom-card dashboard-inner-card">
                                    <div class="card-header justify-content-between">
                                        <div class="card-title">Low Stock Alert</div>
                                        <a href="{{ route('admin.export.low-stock') }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-download"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Medicine</th>
                                                        <th>Alert Qty</th>
                                                        <th>Current Stock</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($lowStockProducts as $item)
                                                        <tr>
                                                            <td>{{ $item->product_name }}</td>
                                                            <td>{{ $item->alert_quantity }}</td>
                                                            <td>
                                                                <span class="report-badge {{ $item->current_stock == 0 ? 'report-badge-danger' : 'report-badge-warning' }}">
                                                                    {{ $item->current_stock }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="summary-empty">No low stock items right now.</td>
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
                                            <i class="fa fa-download"></i> Excel
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive dashboard-table-wrap">
                                            <table class="table table-striped summary-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Medicine</th>
                                                        <th>Batch Number</th>
                                                        <th>Expiry Date</th>
                                                        <th>Days Remaining</th>
                                                        <th>Stock Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($expiryAlerts as $batch)
                                                        <tr class="{{ $batch->alert_row_class }}">
                                                            <td>{{ $batch->product?->product_name ?? '-' }}</td>
                                                            <td>{{ $batch->batch_no ?: '-' }}</td>
                                                            <td>{{ $batch->expiry_show }}</td>
                                                            <td>{{ $batch->days_left }}</td>
                                                            <td>{{ $batch->quantity }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="summary-empty">No batches are expiring inside the next 30 days.</td>
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
                </div>
            </div>
        </div>
    </div>
@endsection
