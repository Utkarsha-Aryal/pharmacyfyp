@extends('backend.layouts.main')

@section('title')
    Expiry Alert
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Expiry Alert Report</h5>
                <p class="mb-0 text-muted">Batch wise expiry list for tracking damaged or near expiry stock.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.expiry-alert') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.expiry-alert-pdf') }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Expired</p>
                        <h3 class="summary-card-value">{{ $expiredCount }}</h3>
                        <span class="summary-card-note">Already crossed expiry date.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Near Expiry</p>
                        <h3 class="summary-card-value">{{ $nearCount }}</h3>
                        <span class="summary-card-note">Inside next 60 days window.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Safe Batch</p>
                        <h3 class="summary-card-value">{{ $safeCount }}</h3>
                        <span class="summary-card-note">Expiry date is still safe.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Expiry List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered js-datatable" data-page-length="10" data-searchable="true">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Supplier</th>
                                <th>Expiry</th>
                                <th>Days Left</th>
                                <th>Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expiryItems as $index => $item)
                                <tr class="{{ $item->expiry_state === 'expired' || $item->expiry_state === 'critical' ? 'table-danger' : ($item->expiry_state === 'warning' ? 'table-warning' : ($item->expiry_state === 'near' ? 'table-info' : '')) }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                                    <td>{{ $item->batch_number ?? '-' }}</td>
                                    <td>{{ $item->supplier?->supplier_name ?? '-' }}</td>
                                    <td>{{ $item->expiry_show }}</td>
                                    <td>{{ $item->days_left }}</td>
                                    <td>{{ $item->quantity_available }}</td>
                                    <td>
                                        <span class="report-badge {{ $item->expiry_state === 'expired' || $item->expiry_state === 'critical' ? 'report-badge-danger' : ($item->expiry_state === 'warning' ? 'report-badge-warning' : 'report-badge-info') }}">
                                            {{ strtoupper($item->expiry_state) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No expiry data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
