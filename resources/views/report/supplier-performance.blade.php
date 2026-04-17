@extends('layouts.main')

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
                <a href="{{ route('admin.export.supplier-performance-pdf') }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Supplier Summary</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="supplierPerformanceTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Supplier</th>
                                <th>Total Orders</th>
                                <th>Total Value</th>
                                <th>Outstanding</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            window.supplierPerformanceTable = window.initServerSideDataTable({
                selector: '#supplierPerformanceTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'supplier' },
                    { data: 'total_orders' },
                    { data: 'total_value' },
                    { data: 'outstanding' },
                ],
                ajaxUrl: '{{ route('admin.report.suppliers.list') }}',
            });
        });
    </script>
@endsection
