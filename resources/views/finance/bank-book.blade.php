@extends('layouts.main')

@section('title')
    Bank Book
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Bank Book</h5>
                <p class="mb-0 text-muted">Bank inflow and outflow from customer payments and expense payments.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.bank-book', request()->query()) }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.bank-book-pdf', request()->query()) }}" target="_blank" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.export.bank-book-pdf', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div id="bankBookPrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Bank book report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>From:</strong> {{ $filters['date_from'] ?? 'Start' }}</span>
                    <span><strong>To:</strong> {{ $filters['date_to'] ?? 'Today' }}</span>
                    <span><strong>Debit:</strong> {{ money_value($summary['debit']) }}</span>
                    <span><strong>Credit:</strong> {{ money_value($summary['credit']) }}</span>
                    <span><strong>Net Bank:</strong> {{ money_value($summary['debit'] - $summary['credit']) }}</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card custom-card summary-card summary-card-green">
                        <div class="card-body">
                            <p class="summary-card-label">Debit</p>
                            <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                            <span class="summary-card-note">Bank received into business.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card custom-card summary-card summary-card-red">
                        <div class="card-body">
                            <p class="summary-card-label">Credit</p>
                            <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                            <span class="summary-card-note">Bank paid out of business.</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card custom-card summary-card summary-card-blue">
                        <div class="card-body">
                            <p class="summary-card-label">Net Bank</p>
                            <h3 class="summary-card-value">{{ money_value($summary['debit'] - $summary['credit']) }}</h3>
                            <span class="summary-card-note">Debit minus credit.</span>
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
                                <a href="{{ route('admin.finance.bank-book') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                    <i class="fa-solid fa-rotate-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Bank Entries</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bankBookTable" class="table table-bordered align-middle w-100">
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
            window.bankBookTable = window.initServerSideDataTable({
                selector: '#bankBookTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'reference' },
                    { data: 'party' },
                    { data: 'entry' },
                    { data: 'amount' },
                    { data: 'notes' },
                ],
                ajaxUrl: '{{ route('admin.finance.bank-book.list') }}',
                ajaxData: function (request) {
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.bankBookTable) {
                    window.bankBookTable.draw();
                }
            });
        });
    </script>
@endsection
