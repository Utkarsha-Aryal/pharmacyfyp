@extends('layouts.main')

@section('title')
    Low Stock Report
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Low Stock Report</h5>
                <p class="mb-0 text-muted">Products that are below or equal to alert quantity.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.low-stock') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.low-stock-pdf') }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Low Stock Items</p>
                        <h3 class="summary-card-value">{{ $lowStockCount }}</h3>
                        <span class="summary-card-note">Need to purchase soon.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Zero Stock</p>
                        <h3 class="summary-card-value">{{ $zeroStockCount }}</h3>
                        <span class="summary-card-note">Out of stock right now.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Safe Stock</p>
                        <h3 class="summary-card-value">{{ $safeStockCount }}</h3>
                        <span class="summary-card-note">Products above alert level.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Low Stock List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered js-datatable" data-page-length="10" data-searchable="true">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Reorder Level</th>
                                <th>Current Stock</th>
                                <th>Deficit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockProducts as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->category_name ?? '-' }}</td>
                                    <td>{{ $item->reorder_level }}</td>
                                    <td>{{ $item->current_stock }}</td>
                                    <td>{{ max(0, (int) $item->reorder_level - (int) $item->current_stock) }}</td>
                                    <td>
                                        <span class="report-badge {{ $item->current_stock == 0 ? 'report-badge-danger' : 'report-badge-warning' }}">
                                            {{ $item->current_stock == 0 ? 'Out of Stock' : 'Low Stock' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No low stock item right now.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
