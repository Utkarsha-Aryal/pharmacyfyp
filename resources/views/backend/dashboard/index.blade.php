@extends('backend.layouts.main')

@section('title')
    Dashboard
@endsection

@section('styles')
    <style>
        .dashboard-wrap {
            padding-bottom: 24px;
        }

        .dashboard-mini-card {
            height: 100%;
            border: 1px solid #e8edf6;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }

        .dashboard-mini-card .card-body {
            padding: 18px 18px 16px;
        }

        .dashboard-mini-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .dashboard-mini-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
            margin-bottom: 0;
        }

        .dashboard-mini-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .dashboard-mini-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .dashboard-mini-note {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 0;
        }

        .dashboard-icon-blue {
            background: rgba(1, 98, 232, 0.12);
            color: #0162e8;
        }

        .dashboard-icon-green {
            background: rgba(34, 192, 60, 0.12);
            color: #22c03c;
        }

        .dashboard-icon-orange {
            background: rgba(253, 126, 20, 0.12);
            color: #fd7e14;
        }

        .dashboard-icon-red {
            background: rgba(238, 51, 94, 0.12);
            color: #ee335e;
        }

        .dashboard-chart-box {
            min-height: 330px;
        }

        .dashboard-note-list li {
            padding: 11px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .dashboard-note-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .dashboard-note-list strong {
            color: #1e293b;
            font-size: 15px;
        }

        .dashboard-note-list span {
            color: #64748b;
            font-size: 13px;
            display: block;
            margin-top: 2px;
        }

        .summary-table td,
        .summary-table th {
            vertical-align: middle;
        }

        .summary-empty {
            padding: 18px 12px !important;
            text-align: center;
            color: #64748b;
        }
    </style>
@endsection

@section('main-content')
    <div class="dashboard-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Dashboard</h5>
                <p class="mb-0 text-muted">Simple summary for stock, supplier and expiry tracking.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Category</p>
                            <span class="dashboard-mini-icon dashboard-icon-blue">
                                <i class="fa-solid fa-list"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalCategory }}</div>
                        <p class="dashboard-mini-note">Medicine groups added in system.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Product</p>
                            <span class="dashboard-mini-icon dashboard-icon-green">
                                <i class="fa-solid fa-capsules"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalProducts }}</div>
                        <p class="dashboard-mini-note">Product master records in inventory.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Supplier</p>
                            <span class="dashboard-mini-icon dashboard-icon-orange">
                                <i class="fa-solid fa-truck-field"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalSuppliers }}</div>
                        <p class="dashboard-mini-note">Supplier records for purchase tracking.</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card custom-card dashboard-mini-card">
                    <div class="card-body">
                        <div class="dashboard-mini-head">
                            <p class="dashboard-mini-title">Total Batch</p>
                            <span class="dashboard-mini-icon dashboard-icon-red">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </span>
                        </div>
                        <div class="dashboard-mini-value">{{ $totalBatches }}</div>
                        <p class="dashboard-mini-note">Batch records for stock and expiry.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">System Overview</div>
                    </div>
                    <div class="card-body dashboard-chart-box">
                        <canvas id="overviewChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">Quick Summary</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 dashboard-note-list">
                            <li>
                                <strong>Total Stock Qty: {{ $totalStock }}</strong>
                                <span>Current quantity from active batches.</span>
                            </li>
                            <li>
                                <strong>Low Stock Items: {{ $lowStockCount }}</strong>
                                <span>Items at or below alert quantity.</span>
                            </li>
                            <li>
                                <strong>Expiring Soon: {{ $expiringSoonCount }}</strong>
                                <span>Batch expiry within next 30 days.</span>
                            </li>
                            <li>
                                <strong>System Users: {{ $totalUsers }}</strong>
                                <span>Users who can open the system.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Low Stock Alert</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped summary-table">
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
                                                <span class="badge {{ $item->current_stock == 0 ? 'bg-danger' : 'bg-warning' }}">
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
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Expiry Alert</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped summary-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Batch</th>
                                        <th>Expiry</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($expiringSoon as $item)
                                        <tr>
                                            <td>{{ $item->product?->product_name ?? 'N/A' }}</td>
                                            <td>{{ $item->batch_no ?? 'N/A' }}</td>
                                            <td>{{ $item->expiry_show }}</td>
                                            <td>{{ $item->quantity }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="summary-empty">No batch is near expiry.</td>
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
@endsection

@section('script')
    <script src="{{ asset('backpanel/assets/libs/chart.js/chart.min.js') }}"></script>
    <script>
        // small chart for dashboard quick look
        $(document).ready(function() {
            var chartElement = document.getElementById('overviewChart');

            if (!chartElement) {
                return;
            }

            new Chart(chartElement, {
                type: 'bar',
                data: {
                    labels: @json($overviewChart['labels']),
                    datasets: [{
                        label: 'Count',
                        data: @json($overviewChart['values']),
                        backgroundColor: [
                            'rgba(1, 98, 232, 0.75)',
                            'rgba(34, 192, 60, 0.75)',
                            'rgba(253, 126, 20, 0.75)',
                            'rgba(111, 66, 193, 0.75)',
                            'rgba(238, 51, 94, 0.75)'
                        ],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.12)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
