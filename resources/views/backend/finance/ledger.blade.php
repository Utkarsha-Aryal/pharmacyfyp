@extends('backend.layouts.main')

@section('title')
    General Ledger
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">General Ledger</h5>
                <p class="mb-0 text-muted">All accounting rows in one filtered list for quick review.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.ledger', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-print js-print-trigger" data-print-target="#financeLedgerPrintArea">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>

        <div id="financeLedgerPrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>General ledger report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>Party Type:</strong> {{ $filters['party_type'] ?? 'All' }}</span>
                    <span><strong>Account:</strong> {{ $filters['account_type'] ?? 'All' }}</span>
                    <span><strong>Entry:</strong> {{ $filters['entry_type'] ?? 'All' }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Debit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                        <span class="summary-card-note">Money moved into accounts.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Credit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                        <span class="summary-card-note">Money moved out of accounts.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Cash</p>
                        <h3 class="summary-card-value">{{ money_value($summary['cash']) }}</h3>
                        <span class="summary-card-note">Cash side transactions.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Bank</p>
                        <h3 class="summary-card-value">{{ money_value($summary['bank']) }}</h3>
                        <span class="summary-card-note">Bank side transactions.</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Party Type</label>
                        <select name="party_type" class="form-select js-select2" data-placeholder="All" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="customer" @selected(($filters['party_type'] ?? '') === 'customer')>Customer</option>
                            <option value="supplier" @selected(($filters['party_type'] ?? '') === 'supplier')>Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select js-select2" data-placeholder="All" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="cash" @selected(($filters['account_type'] ?? '') === 'cash')>Cash</option>
                            <option value="bank" @selected(($filters['account_type'] ?? '') === 'bank')>Bank</option>
                            <option value="receivable" @selected(($filters['account_type'] ?? '') === 'receivable')>Receivable</option>
                            <option value="payable" @selected(($filters['account_type'] ?? '') === 'payable')>Payable</option>
                            <option value="expense" @selected(($filters['account_type'] ?? '') === 'expense')>Expense</option>
                            <option value="income" @selected(($filters['account_type'] ?? '') === 'income')>Income</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Entry Type</label>
                        <select name="entry_type" class="form-select js-select2" data-placeholder="All" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="debit" @selected(($filters['entry_type'] ?? '') === 'debit')>Debit</option>
                            <option value="credit" @selected(($filters['entry_type'] ?? '') === 'credit')>Credit</option>
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
                    <div class="col-md-2">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm icon-only-btn" title="Apply Filter" aria-label="Apply Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.finance.ledger') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Ledger Entries</div>
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
                                <th>Account</th>
                                <th>Entry</th>
                                <th>Amount</th>
                                <th>Notes</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $index => $transaction)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $transaction->transaction_date_show }}</td>
                                    <td>{{ $transaction->reference_type ? $transaction->reference_type . ' #' . $transaction->reference_id : '-' }}</td>
                                    <td>{{ $transaction->party_name }}</td>
                                    <td>{{ $transaction->account_label }}</td>
                                    <td><span class="report-badge {{ $transaction->entry_type === 'debit' ? 'report-badge-success' : 'report-badge-danger' }}">{{ $transaction->entry_label }}</span></td>
                                    <td>{{ money_value($transaction->amount) }}</td>
                                    <td>{{ $transaction->notes ?: '-' }}</td>
                                    <td>{{ $transaction->creator?->name ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No ledger entry found.</td>
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
