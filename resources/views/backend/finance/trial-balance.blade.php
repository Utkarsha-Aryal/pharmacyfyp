@extends('backend.layouts.main')

@section('title')
    Trial Balance
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Trial Balance</h5>
                <p class="mb-0 text-muted">Simple debit and credit totals from saved accounting rows.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.trial-balance') }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-print js-print-trigger" data-print-target="#trialBalancePrintArea">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </div>

        <div id="trialBalancePrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Trial balance report</p>
                <div class="print-sheet-meta">
                    <span><strong>Generated:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                    <span><strong>Total Debit:</strong> {{ money_value($summary['debit']) }}</span>
                    <span><strong>Total Credit:</strong> {{ money_value($summary['credit']) }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Total Debit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['debit']) }}</h3>
                        <span class="summary-card-note">All debit rows together.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Total Credit</p>
                        <h3 class="summary-card-value">{{ money_value($summary['credit']) }}</h3>
                        <span class="summary-card-note">All credit rows together.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Account Summary</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Account</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $row['account_type'])) }}</td>
                                    <td>{{ money_value($row['debit']) }}</td>
                                    <td>{{ money_value($row['credit']) }}</td>
                                    <td>
                                        <span class="report-badge {{ $row['difference'] >= 0 ? 'report-badge-success' : 'report-badge-danger' }}">
                                            {{ money_value($row['difference']) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No account summary available.</td>
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
