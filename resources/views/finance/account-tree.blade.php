@extends('layouts.main')

@section('title')
    Account Tree
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Account Tree</h5>
                <p class="mb-0 text-muted">Simple chart of accounts view with group totals and current book movement.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.finance.vouchers.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Voucher
                </a>
                <a href="{{ route('admin.export.account-tree-pdf', request()->query()) }}" target="_blank" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.export.account-tree-pdf', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div id="accountTreePrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Account tree / chart of accounts</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>From:</strong> {{ $filters['date_from'] ?? 'Start' }}</span>
                    <span><strong>To:</strong> {{ $filters['date_to'] ?? 'Today' }}</span>
                    <span><strong>Total Accounts:</strong> {{ $summary['accounts'] }}</span>
                    <span><strong>Total Debit:</strong> {{ money_value($summary['debit']) }}</span>
                    <span><strong>Total Credit:</strong> {{ money_value($summary['credit']) }}</span>
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
                                <a href="{{ route('admin.finance.account-tree') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-blue">
                        <div class="card-body">
                            <p class="summary-card-label">Account Heads</p>
                            <h3 class="summary-card-value">{{ $summary['accounts'] }}</h3>
                            <span class="summary-card-note">Ledger accounts grouped for finance use.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-green">
                        <div class="card-body">
                            <p class="summary-card-label">Debit Movement</p>
                            <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                            <span class="summary-card-note">Total debit side movement.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-red">
                        <div class="card-body">
                            <p class="summary-card-label">Credit Movement</p>
                            <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                            <span class="summary-card-note">Total credit side movement.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card mb-4">
                <div class="card-body">
                    <label for="accountTreeSearch" class="form-label mb-2">Search Accounts</label>
                    <input type="text" id="accountTreeSearch" class="form-control" placeholder="Search account code, name, group or balance">
                </div>
            </div>

            @foreach ($groups as $group)
                <div class="card custom-card mb-4 account-tree-group-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">{{ $group['name'] }}</div>
                        <div class="d-flex gap-3 small text-muted">
                            <span>Debit: {{ money_value($group['debit']) }}</span>
                            <span>Credit: {{ money_value($group['credit']) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">S.No</th>
                                        <th>Code</th>
                                        <th>Account Name</th>
                                        <th>Normal Side</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th>Closing Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['rows'] as $index => $row)
                                        <tr class="account-tree-row">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row['code'] }}</td>
                                            <td>{{ $row['name'] }}</td>
                                            <td><span class="report-badge {{ $row['nature'] === 'DEBIT' ? 'report-badge-success' : 'report-badge-info' }}">{{ $row['nature'] }}</span></td>
                                            <td>{{ money_value($row['debit']) }}</td>
                                            <td>{{ money_value($row['credit']) }}</td>
                                            <td>
                                                <span class="report-badge {{ $row['closing_side'] === 'Dr' ? 'report-badge-success' : 'report-badge-warning' }}">
                                                    {{ money_value($row['closing_amount']) }} {{ $row['closing_side'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchInput = document.getElementById('accountTreeSearch');

            if (!searchInput) {
                return;
            }

            // Account tree is grouped, so we keep native table rows and filter each group card carefully.
            function filterAccountTreeRows() {
                var keyword = searchInput.value.toLowerCase().trim();

                document.querySelectorAll('.account-tree-group-card').forEach(function (card) {
                    var visibleRows = 0;

                    card.querySelectorAll('.account-tree-row').forEach(function (row) {
                        var isVisible = keyword === '' || row.innerText.toLowerCase().indexOf(keyword) !== -1;
                        row.classList.toggle('d-none', !isVisible);

                        if (isVisible) {
                            visibleRows += 1;
                        }
                    });

                    card.classList.toggle('d-none', keyword !== '' && visibleRows === 0);
                });
            }

            searchInput.addEventListener('input', filterAccountTreeRows);
        });
    </script>
@endsection
