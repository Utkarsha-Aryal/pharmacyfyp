@extends('backend.layouts.main')

@section('title')
    Sales Invoice
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Sales Invoice</h5>
                <p class="mb-0 text-muted">Retail billing, wholesale, credit sales and return tracking.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Create Invoice
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">This Month</p>
                        <h3 class="summary-card-value">{{ money_value($summary['this_month']) }}</h3>
                        <span class="summary-card-note">Invoice value for this month.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">All Time</p>
                        <h3 class="summary-card-value">{{ money_value($summary['all_time']) }}</h3>
                        <span class="summary-card-note">Complete sales value together.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Receivable</p>
                        <h3 class="summary-card-value">{{ money_value($summary['receivable']) }}</h3>
                        <span class="summary-card-note">Still pending from customers.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Credit Sales</p>
                        <h3 class="summary-card-value">{{ $summary['credit'] }}</h3>
                        <span class="summary-card-note">Invoices created on credit.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.sales.index') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Party</label>
                        <select name="customer_id" class="form-select js-select2" data-placeholder="All party" data-allow-clear="1">
                            <option value="">All Party</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sale Type</label>
                        <select name="sale_type" class="form-select js-select2" data-placeholder="All type" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="retail" @selected(($filters['sale_type'] ?? '') === 'retail')>Retail</option>
                            <option value="wholesale" @selected(($filters['sale_type'] ?? '') === 'wholesale')>Wholesale</option>
                            <option value="credit" @selected(($filters['sale_type'] ?? '') === 'credit')>Credit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select js-select2" data-placeholder="All status" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
                            <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmed</option>
                            <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment</label>
                        <select name="payment_status" class="form-select js-select2" data-placeholder="All payment" data-allow-clear="1">
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
                            <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Invoice List</div>
                <a href="{{ route('admin.export.sales-invoices', request()->query()) }}" class="btn btn-outline-primary btn-sm btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesInvoiceTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.sales.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Reference</th>
                                <th>Party</th>
                                <th>Date</th>
                                <th>Sale Type</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Total</th>
                                <th>Due</th>
                                <th style="width: 180px;">Action</th>
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
            window.salesInvoiceTable = window.initServerSideDataTable({
                selector: '#salesInvoiceTable',
                pageLength: 10,
                sort: false,
                columns: [
                    { data: 'sno' },
                    { data: 'reference' },
                    { data: 'customer' },
                    { data: 'date' },
                    { data: 'sale_type' },
                    { data: 'status' },
                    { data: 'payment' },
                    { data: 'total' },
                    { data: 'due' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.sales.list') }}',
                ajaxData: function (request) {
                    request.customer_id = $('[name="customer_id"]').val() || '';
                    request.sale_type = $('[name="sale_type"]').val() || '';
                    request.status = $('[name="status"]').val() || '';
                    request.payment_status = $('[name="payment_status"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.salesInvoiceTable) {
                    window.salesInvoiceTable.draw();
                }
            });
        });
    </script>
@endsection
