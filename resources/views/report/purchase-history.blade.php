@extends('layouts.main')

@section('title')
    Purchase History Report
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase History Report</h5>
                <p class="mb-0 text-muted">Filter purchase orders by supplier, date, status and payment.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.purchase-history', request()->query()) }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.purchase-history-pdf', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <form action="{{ route('admin.report.purchases') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select js-select2" data-placeholder="All supplier" data-allow-clear="1">
                            <option value="">All Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select js-select2">
                            <option value="">All</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                            <option value="received" @selected(($filters['status'] ?? '') === 'received')>Received</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment</label>
                        <select name="payment_status" class="form-select js-select2">
                            <option value="">All</option>
                            <option value="unpaid" @selected(($filters['payment_status'] ?? '') === 'unpaid')>Unpaid</option>
                            <option value="partial" @selected(($filters['payment_status'] ?? '') === 'partial')>Partial</option>
                            <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-1">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm icon-only-btn" title="Apply Filter" aria-label="Apply Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.report.purchases') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Report Table</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="purchaseHistoryTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Due</th>
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
            window.purchaseHistoryTable = window.initServerSideDataTable({
                selector: '#purchaseHistoryTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'reference' },
                    { data: 'supplier' },
                    { data: 'date' },
                    { data: 'status' },
                    { data: 'payment' },
                    { data: 'total' },
                    { data: 'due' },
                ],
                ajaxUrl: '{{ route('admin.report.purchases.list') }}',
                ajaxData: function (request) {
                    request.supplier_id = $('[name="supplier_id"]').val() || '';
                    request.status = $('[name="status"]').val() || '';
                    request.payment_status = $('[name="payment_status"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.purchaseHistoryTable) {
                    window.purchaseHistoryTable.draw();
                }
            });
        });
    </script>
@endsection
