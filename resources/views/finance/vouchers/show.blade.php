@extends('layouts.main')

@section('title')
    Voucher Details
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Voucher Details</h5>
                <p class="mb-0 text-muted">Review the posted voucher and its ledger lines.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.finance.ledger') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.finance.vouchers.edit', $voucher) }}" class="btn btn-outline-warning">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>
                <form action="{{ route('admin.finance.vouchers.delete', $voucher) }}" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete voucher?" data-confirm-text="This will remove the voucher and its accounting entries." data-confirm-button="Yes, delete it">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted">Voucher No</label>
                        <div class="fw-semibold">{{ $voucher->voucher_no }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Voucher Type</label>
                        <div class="fw-semibold">{{ $voucher->voucher_type_label }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Voucher Date</label>
                        <div class="fw-semibold">{{ $voucher->voucher_date_show }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Created By</label>
                        <div class="fw-semibold">{{ $voucher->creator?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted">Notes</label>
                        <div class="fw-semibold">{{ $voucher->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Voucher Lines</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Account</th>
                                <th>Party</th>
                                <th>Entry</th>
                                <th>Amount</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $debitTotal = 0;
                                $creditTotal = 0;
                            @endphp
                            @foreach ($voucher->entries as $index => $entry)
                                @php
                                    if ($entry->entry_type === 'debit') {
                                        $debitTotal += (float) $entry->amount;
                                    } else {
                                        $creditTotal += (float) $entry->amount;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $entry->account_label }}</td>
                                    <td>{{ $entry->party_name }}</td>
                                    <td><span class="badge {{ $entry->entry_type === 'debit' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($entry->entry_type) }}</span></td>
                                    <td>{{ money_value($entry->amount) }}</td>
                                    <td>{{ $entry->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Debit Total</th>
                                <th>{{ money_value($debitTotal) }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Credit Total</th>
                                <th>{{ money_value($creditTotal) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
