@extends('layouts.main')

@section('title')
    Trial Balance
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Trial Balance</h5>
                <p class="mb-0 text-muted">Balanced debit and credit view for accountants and final checking.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.trial-balance', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.trial-balance-pdf', request()->query()) }}" target="_blank" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.export.trial-balance-pdf', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div id="trialBalancePrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Trial balance report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>From:</strong> {{ $filters['date_from'] ?? 'Start' }}</span>
                    <span><strong>To:</strong> {{ $filters['date_to'] ?? 'Today' }}</span>
                    <span><strong>Total Debit:</strong> {{ money_value($summary['debit']) }}</span>
                    <span><strong>Total Credit:</strong> {{ money_value($summary['credit']) }}</span>
                    <span><strong>Difference:</strong> {{ money_value($summary['difference']) }}</span>
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
                                <a href="{{ route('admin.finance.trial-balance') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row g-3 mb-4">
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-green">
                        <div class="card-body">
                            <p class="summary-card-label">Total Debit</p>
                            <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                            <span class="summary-card-note">Debit side of the books.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-red">
                        <div class="card-body">
                            <p class="summary-card-label">Total Credit</p>
                            <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                            <span class="summary-card-note">Credit side of the books.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card summary-card summary-card-blue">
                        <div class="card-body">
                            <p class="summary-card-label">Difference</p>
                            <h3 class="summary-card-value">{{ money_value($summary['difference']) }}</h3>
                            <span class="summary-card-note">This should normally stay at zero.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between align-items-center">
                    <div class="card-title">Trial Balance Rows</div>
                    <input type="text" id="trialBalanceSearch" class="form-control w-auto" placeholder="Search account rows">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">S.No</th>
                                    <th>Code</th>
                                    <th>Account</th>
                                    <th>Group</th>
                                    <th>Normal Side</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Closing Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $serial = 1; @endphp
                                @forelse ($rowGroups as $groupName => $groupRows)
                                    @foreach ($groupRows as $index => $row)
                                        @php $groupSlug = \Illuminate\Support\Str::slug($groupName); @endphp
                                        <tr class="trial-balance-row" data-group="{{ $groupSlug }}">
                                            <td>{{ $serial++ }}</td>
                                            <td>{{ $row['code'] }}</td>
                                            <td>{{ $row['name'] }}</td>
                                            <td>{{ $groupName }}</td>
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
                                    <tr class="table-light trial-balance-total-row" data-group-total="{{ \Illuminate\Support\Str::slug($groupName) }}">
                                        <td colspan="5" class="text-end fw-semibold">{{ $groupName }} Total</td>
                                        <td class="fw-semibold">{{ money_value($groupRows->sum('debit')) }}</td>
                                        <td class="fw-semibold">{{ money_value($groupRows->sum('credit')) }}</td>
                                        <td></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No account summary available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="5" class="text-end">Grand Total</th>
                                    <th>{{ money_value($summary['debit']) }}</th>
                                    <th>{{ money_value($summary['credit']) }}</th>
                                    <th>{{ money_value($summary['difference']) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchInput = document.getElementById('trialBalanceSearch');

            if (!searchInput) {
                return;
            }

            // This report has subtotal rows, so simple table search is safer than DataTables here.
            function filterTrialBalanceRows() {
                var keyword = searchInput.value.toLowerCase().trim();
                var visibleGroupCount = {};

                document.querySelectorAll('.trial-balance-row').forEach(function (row) {
                    var isVisible = keyword === '' || row.innerText.toLowerCase().indexOf(keyword) !== -1;
                    row.classList.toggle('d-none', !isVisible);

                    if (isVisible) {
                        visibleGroupCount[row.dataset.group] = (visibleGroupCount[row.dataset.group] || 0) + 1;
                    }
                });

                document.querySelectorAll('.trial-balance-total-row').forEach(function (row) {
                    var groupKey = row.dataset.groupTotal;
                    var shouldShow = keyword === '' || (visibleGroupCount[groupKey] || 0) > 0;
                    row.classList.toggle('d-none', !shouldShow);
                });
            }

            searchInput.addEventListener('input', filterTrialBalanceRows);
        });
    </script>
@endsection
