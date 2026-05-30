@extends('layouts.main')

@section('title')
    Sales Invoice Detail
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $invoice->reference }}</h5>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-dark">
                    <i class="fa-solid fa-rotate-left"></i> Sales Returns
                </a>
                <a href="{{ route('admin.sales-invoices.print', $invoice) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fa-solid fa-wallet"></i> Payment
                </button>
                <a href="{{ route('admin.sales.returns.create', ['sales_invoice_id' => $invoice->id]) }}" class="btn btn-outline-danger">
                    <i class="fa-solid fa-rotate-left"></i> Create Return
                </a>
            </div>
        </div>

        <div id="salesInvoicePrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Sales invoice print copy</p>
                <div class="print-sheet-meta">
                    <span><strong>Invoice:</strong> {{ $invoice->reference }}</span>
                    <span><strong>Date:</strong> {{ $invoice->invoice_date_show }}</span>
                    <span><strong>Party:</strong> {{ $invoice->customer?->name ?? '-' }}</span>
                    <span><strong>Printed:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Total Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->total_amount) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Paid Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->paid_amount) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Due Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->due_amount) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Payment</p>
                        <h3 class="summary-card-value">{{ $invoice->payment_label }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Customer</strong>
                        <span>{{ $invoice->customer?->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Sale Type</strong>
                        <span>{{ $invoice->sale_type_label }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Date</strong>
                        <span>{{ $invoice->invoice_date_show }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Status</strong>
                        <span class="report-badge {{ $invoice->status_badge_class }}">{{ $invoice->status_label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Invoice Items</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesInvoiceItemsTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Free Qty</th>
                                <th>MRP</th>
                                <th>Unit Price</th>
                                <th>Discount %</th>
                                <th>CC Rate %</th>
                                <th>Free Goods Value</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Return History</div>
                <a href="{{ route('admin.sales.returns.create', ['sales_invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-plus"></i> Create Return
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesInvoiceReturnsTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Discount %</th>
                                <th>Discount Amt</th>
                                <th>Net Rate</th>
                                <th>Refund</th>
                                <th>Settlement</th>
                                <th>Reason</th>
                                <th style="width: 130px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.sales.payment', $invoice) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Update Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="unpaid" @selected($invoice->payment_status === 'unpaid')>Unpaid</option>
                                        <option value="partial" @selected($invoice->payment_status === 'partial')>Partial</option>
                                        <option value="paid" @selected($invoice->payment_status === 'paid')>Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode_id" class="form-select">
                                        <option value="">Select mode</option>
                                        @foreach ($paymentModes as $mode)
                                            <option value="{{ $mode->id }}" @selected((int) $invoice->payment_mode_id === (int) $mode->id)>{{ $mode->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Paid Amount</label>
                                    <input type="number" min="0" step="0.01" name="paid_amount" class="form-control" value="{{ $invoice->paid_amount }}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            window.salesInvoiceItemsTable = window.initServerSideDataTable({
                selector: '#salesInvoiceItemsTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'product' },
                    { data: 'batch' },
                    { data: 'qty' },
                    { data: 'free_qty' },
                    { data: 'mrp' },
                    { data: 'unit_price' },
                    { data: 'discount_percent' },
                    { data: 'cc_rate' },
                    { data: 'free_goods_value' },
                    { data: 'subtotal' },
                ],
                ajaxUrl: '{{ route('admin.sales.items.list', $invoice) }}',
            });

            window.salesInvoiceReturnsTable = window.initServerSideDataTable({
                selector: '#salesInvoiceReturnsTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'product' },
                    { data: 'batch' },
                    { data: 'qty' },
                    { data: 'discount_percent' },
                    { data: 'discount_amount' },
                    { data: 'net_rate' },
                    { data: 'refund' },
                    { data: 'settlement' },
                    { data: 'reason' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.sales.returns.history.list', $invoice) }}',
            });

            var hash = window.location.hash || '';

            if (hash === '#paymentModal') {
                var paymentModalEl = document.getElementById('paymentModal');
                if (paymentModalEl) {
                    bootstrap.Modal.getOrCreateInstance(paymentModalEl).show();
                }
            }
        });
    </script>
@endsection
