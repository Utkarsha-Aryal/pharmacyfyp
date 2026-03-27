@extends('backend.layouts.main')

@section('title')
    Supplier Performance Report
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Supplier Performance Report</h5>
                <p class="mb-0 text-muted">See total orders, value and outstanding amount per supplier.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.supplier-performance') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.supplier-performance-pdf') }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Supplier Summary</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10" data-searchable="true">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Supplier</th>
                                <th>Total Orders</th>
                                <th>Total Value</th>
                                <th>Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $index => $supplier)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $supplier->supplier_name }}</td>
                                    <td>{{ $supplier->total_orders }}</td>
                                    <td>{{ money_value($supplier->total_value) }}</td>
                                    <td>
                                        @if ((float) $supplier->outstanding_amount > 0)
                                            <span class="text-danger fw-semibold">{{ money_value($supplier->outstanding_amount) }}</span>
                                        @else
                                            <span class="report-badge report-badge-success">Paid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No supplier data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
