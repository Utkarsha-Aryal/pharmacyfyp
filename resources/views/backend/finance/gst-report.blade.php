@extends('backend.layouts.main')

@section('title')
    GST Report
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">GST / Tax Report</h5>
                <p class="mb-0 text-muted">Sales tax collected from confirmed invoices.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.gst-report', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.gst-report-pdf', request()->query()) }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <button type="button" class="btn btn-print js-print-trigger" data-print-target="#gstReportPrintArea">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>

        <div id="gstReportPrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>GST / tax report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>From:</strong> {{ $filters['date_from'] ?? 'Start' }}</span>
                    <span><strong>To:</strong> {{ $filters['date_to'] ?? 'Today' }}</span>
                    <span><strong>Default Tax Rate:</strong> {{ default_tax_rate() }}%</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Taxable Sales</p>
                        <h3 class="summary-card-value">{{ money_value($summary['taxable_sales']) }}</h3>
                        <span class="summary-card-note">Invoice subtotal before tax.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Tax Collected</p>
                        <h3 class="summary-card-value">{{ money_value($summary['tax_amount']) }}</h3>
                        <span class="summary-card-note">Sum of invoice tax fields.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Total Sales</p>
                        <h3 class="summary-card-value">{{ money_value($summary['total_sales']) }}</h3>
                        <span class="summary-card-note">Gross sale amount together.</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm icon-only-btn" title="Apply Filter" aria-label="Apply Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.finance.gst-report') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Invoice GST Details</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="15" data-searchable="true">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Invoice</th>
                                <th>Party</th>
                                <th>Date</th>
                                <th>Taxable Sales</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $index => $invoice)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $invoice->reference }}</td>
                                    <td>{{ $invoice->customer?->name ?? '-' }}</td>
                                    <td>{{ $invoice->invoice_date_show }}</td>
                                    <td>{{ money_value($invoice->subtotal) }}</td>
                                    <td>{{ money_value($invoice->tax_amount) }}</td>
                                    <td>{{ money_value($invoice->total_amount) }}</td>
                                    <td><span class="report-badge {{ $invoice->payment_badge_class }}">{{ $invoice->payment_label }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No invoice found for GST report.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection
