@extends('backend.layouts.main')

@section('title')
    Cash Book
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Cash Book</h5>
                <p class="mb-0 text-muted">Cash inflow and outflow from sales, returns and expenses.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.cash-book', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-print js-print-trigger" data-print-target="#cashBookPrintArea">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>

        <div id="cashBookPrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Cash book report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>Debit:</strong> {{ money_value($summary['debit']) }}</span>
                    <span><strong>Credit:</strong> {{ money_value($summary['credit']) }}</span>
                    <span><strong>Net Cash:</strong> {{ money_value($summary['debit'] - $summary['credit']) }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Debit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                        <span class="summary-card-note">Cash received into business.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Credit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                        <span class="summary-card-note">Cash paid out of business.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Net Cash</p>
                        <h3 class="summary-card-value">{{ money_value($summary['debit'] - $summary['credit']) }}</h3>
                        <span class="summary-card-note">Debit minus credit.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Cash Entries</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="15">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Party</th>
                                <th>Entry</th>
                                <th>Amount</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $transaction->transaction_date_show }}</td>
                                    <td>{{ $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-' }}</td>
                                    <td>{{ $transaction->party_name }}</td>
                                    <td><span class="report-badge {{ $transaction->entry_type === 'debit' ? 'report-badge-success' : 'report-badge-danger' }}">{{ $transaction->entry_label }}</span></td>
                                    <td>{{ money_value($transaction->amount) }}</td>
                                    <td>{{ $transaction->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No cash entry available.</td>
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
