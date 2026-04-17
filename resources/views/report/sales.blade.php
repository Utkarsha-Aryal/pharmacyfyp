@extends('layouts.main')

@section('title')
    Sales Report
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Sales Report</h5>
                <p class="mb-0 text-muted">Sales invoices with customer, sale type, payment status and balance details.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.sales-invoices') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.sales-invoices-pdf') }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Invoices</p>
                        <h3 class="summary-card-value">{{ $summary['invoice_count'] }}</h3>
                        <span class="summary-card-note">Confirmed sales bills.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Total Sales</p>
                        <h3 class="summary-card-value">{{ money_value($summary['total_sales']) }}</h3>
                        <span class="summary-card-note">Net invoice total.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Paid</p>
                        <h3 class="summary-card-value">{{ money_value($summary['paid_sales']) }}</h3>
                        <span class="summary-card-note">Money already received.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Due</p>
                        <h3 class="summary-card-value">{{ money_value($summary['due_sales']) }}</h3>
                        <span class="summary-card-note">Still outstanding.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.report.sales') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select js-select2" data-placeholder="All customer" data-allow-clear="1">
                            <option value="">All Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sale Type</label>
                        <select name="sale_type_id" class="form-select js-select2">
                            <option value="">All</option>
                            @foreach ($saleTypes as $saleType)
                                <option value="{{ $saleType->id }}" @selected((string) ($filters['sale_type_id'] ?? '') === (string) $saleType->id)>{{ $saleType->name }}</option>
                            @endforeach
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
                            <a href="{{ route('admin.report.sales') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Sales List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesReportTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Reference</th>
                                <th>Party</th>
                                <th>Date</th>
                                <th>Sale Type</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Paid</th>
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
            window.salesReportTable = window.initServerSideDataTable({
                selector: '#salesReportTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'reference' },
                    { data: 'party' },
                    { data: 'date' },
                    { data: 'sale_type' },
                    { data: 'payment' },
                    { data: 'total' },
                    { data: 'paid' },
                    { data: 'due' },
                ],
                ajaxUrl: '{{ route('admin.report.sales.list') }}',
                ajaxData: function (request) {
                    request.customer_id = $('[name="customer_id"]').val() || '';
                    request.sale_type_id = $('[name="sale_type_id"]').val() || '';
                    request.payment_status = $('[name="payment_status"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.salesReportTable) {
                    window.salesReportTable.draw();
                }
            });
        });
    </script>
@endsection
