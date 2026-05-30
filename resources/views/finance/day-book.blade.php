@extends('layouts.main')

@section('title')
    Day Book
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Day Book</h5>
                <p class="mb-0 text-muted">Chronological accounting entries from sales, purchases, returns, payments, expenses and vouchers.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.finance.vouchers.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Voucher
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">{{ empty($filters['account_type']) ? 'Entries' : 'Opening' }}</p>
                        <h3 class="mb-1">{{ empty($filters['account_type']) ? number_format($summary['entry_count']) : money_value($summary['opening_balance']) }}</h3>
                        <span class="text-muted small">{{ empty($filters['account_type']) ? 'Rows posted in the selected range.' : 'Balance before the selected range.' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Debit</p>
                        <h3 class="mb-1">{{ money_value($summary['debit']) }}</h3>
                        <span class="text-muted small">Debit amount in the selected range.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Credit</p>
                        <h3 class="mb-1">{{ money_value($summary['credit']) }}</h3>
                        <span class="text-muted small">Credit amount in the selected range.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">{{ empty($filters['account_type']) ? 'Difference' : 'Closing' }}</p>
                        <h3 class="mb-1">{{ empty($filters['account_type']) ? money_value($summary['difference']) : money_value($summary['closing_balance']) }}</h3>
                        <span class="text-muted small">{{ empty($filters['account_type']) ? 'Debit minus credit; should be zero.' : 'Opening + debit - credit.' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="GET" class="card custom-card mb-4 day-book-filter">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select js-select2" data-placeholder="All accounts" data-allow-clear="1">
                            <option value="">All Accounts</option>
                            @foreach ($accountCatalog as $account)
                                <option value="{{ $account['key'] }}" @selected(($filters['account_type'] ?? '') === $account['key'])>{{ $account['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                            <a href="{{ route('admin.finance.day-book') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Day Book Entries</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dayBookTable" class="table table-bordered table-sm align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Party</th>
                                <th>Account</th>
                                <th>Narration</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Running Balance</th>
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
            window.dayBookTable = window.initServerSideDataTable({
                selector: '#dayBookTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'reference' },
                    { data: 'party' },
                    { data: 'account' },
                    { data: 'narration' },
                    { data: 'debit' },
                    { data: 'credit' },
                    { data: 'running_balance' },
                ],
                ajaxUrl: '{{ route('admin.finance.day-book.list') }}',
                ajaxData: function (request) {
                    request.account_type = $('[name="account_type"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.day-book-filter select, .day-book-filter input', function () {
                if (window.dayBookTable) {
                    window.dayBookTable.draw();
                }
            });
        });
    </script>
@endsection
