@extends('layouts.main')

@section('title')
    General Ledger
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">General Ledger</h5>
                <p class="mb-0 text-muted">Account wise debit and credit entries in one clean report.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.ledger', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.ledger-pdf', request()->query()) }}" target="_blank" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.export.ledger-pdf', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
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
                            <p class="summary-card-label">Receivable</p>
                            <h3 class="summary-card-value">{{ money_value($summary['receivable']) }}</h3>
                            <span class="summary-card-note">Outstanding customer amount.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card custom-card summary-card summary-card-orange">
                        <div class="card-body">
                            <p class="summary-card-label">Payable</p>
                            <h3 class="summary-card-value">{{ money_value($summary['payable']) }}</h3>
                            <span class="summary-card-note">Outstanding supplier amount.</span>
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
                            <label class="form-label">Party Search</label>
                            <input type="text" name="party_keyword" class="form-control" value="{{ $filters['party_keyword'] ?? '' }}" placeholder="Search party name">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" class="form-select js-select2" data-placeholder="All" data-allow-clear="1">
                                <option value="">All</option>
                                @foreach ($accountCatalog as $account)
                                    <option value="{{ $account['key'] }}" @selected(($filters['account_type'] ?? '') === $account['key'])>{{ $account['name'] }}</option>
                                @endforeach
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
                        <table id="financeLedgerTable" class="table table-bordered align-middle w-100">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">S.No</th>
                                    <th>Date</th>
                                    <th>Voucher / Ref</th>
                                    <th>Party</th>
                                    <th>Account</th>
                                    <th>Group</th>
                                    <th>Narration</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            window.financeLedgerTable = window.initServerSideDataTable({
                selector: '#financeLedgerTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'reference' },
                    { data: 'party' },
                    { data: 'account' },
                    { data: 'group' },
                    { data: 'narration' },
                    { data: 'debit' },
                    { data: 'credit' },
                    { data: 'created_by' },
                ],
                ajaxUrl: '{{ route('admin.finance.ledger.list') }}',
                ajaxData: function (request) {
                    request.party_type = $('[name="party_type"]').val() || '';
                    request.party_keyword = $('[name="party_keyword"]').val() || '';
                    request.account_type = $('[name="account_type"]').val() || '';
                    request.entry_type = $('[name="entry_type"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.financeLedgerTable) {
                    window.financeLedgerTable.draw();
                }
            });
        });
    </script>
@endsection
