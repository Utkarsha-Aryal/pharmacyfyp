@extends('layouts.main')

@section('title')
    Party Ledger
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $customer->name }}</h5>
                <p class="mb-0 text-muted">Ledger, invoices and returns for one party.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.customers.ledger.print', $customer) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div id="partyLedgerPrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Party ledger print copy</p>
                <div class="print-sheet-meta">
                    <span><strong>Party:</strong> {{ $customer->name }}</span>
                    <span><strong>Outstanding:</strong> {{ money_value($outstanding) }}</span>
                    <span><strong>Aging:</strong> {{ $agingDays }} days</span>
                    <span><strong>Printed:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Outstanding</p>
                        <h3 class="summary-card-value">{{ money_value($outstanding) }}</h3>
                        <span class="summary-card-note">Oldest unpaid amount on the party.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Sales Count</p>
                        <h3 class="summary-card-value">{{ $salesCount }}</h3>
                        <span class="summary-card-note">Total invoices linked to this party.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Invoice Total</p>
                        <h3 class="summary-card-value">{{ money_value($invoiceTotal) }}</h3>
                        <span class="summary-card-note">All bill amounts together.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Paid Amount</p>
                        <h3 class="summary-card-value">{{ money_value($paidTotal) }}</h3>
                        <span class="summary-card-note">Money already collected.</span>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs dashboard-tabs mb-3" id="partyLedgerTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#partyInvoices" type="button">Invoices</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#partyReturns" type="button">Returns</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="partyInvoices">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Invoice History</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customerLedgerInvoicesTable" class="table table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">S.No</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Sale Type</th>
                                        <th>Status</th>
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
            <div class="tab-pane fade" id="partyReturns">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Return History</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customerLedgerReturnsTable" class="table table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">S.No</th>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Discount</th>
                                        <th>Net Rate</th>
                                        <th>Refund</th>
                                        <th>Settlement</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            window.customerLedgerInvoicesTable = window.initServerSideDataTable({
                selector: '#customerLedgerInvoicesTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'reference' },
                    { data: 'date' },
                    { data: 'sale_type' },
                    { data: 'status' },
                    { data: 'payment' },
                    { data: 'total' },
                    { data: 'paid' },
                    { data: 'due' },
                ],
                ajaxUrl: '{{ route('admin.customers.ledger.invoices.list', $customer) }}',
            });

            window.customerLedgerReturnsTable = window.initServerSideDataTable({
                selector: '#customerLedgerReturnsTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'product' },
                    { data: 'quantity' },
                    { data: 'discount' },
                    { data: 'net_rate' },
                    { data: 'refund' },
                    { data: 'settlement' },
                    { data: 'reason' },
                ],
                ajaxUrl: '{{ route('admin.customers.ledger.returns.list', $customer) }}',
            });
        });
    </script>
@endsection
