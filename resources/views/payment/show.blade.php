@extends('layouts.main')

@section('title')
    Payment Details
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Payment Details</h5>
                <p class="mb-0 text-muted">One payment voucher with linked bills and party details.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.payments.index', ['edit' => $payment->id, 'open' => $payment->type]) }}" class="btn btn-outline-warning">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Payment
                </a>
                <a href="{{ route('admin.payments.print', $payment) }}" target="_blank" class="btn btn-primary">
                    <i class="fa fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted">Type</label>
                        <div class="fw-semibold">{{ $payment->type === 'in' ? 'Payment In' : 'Payment Out' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Party</label>
                        <div class="fw-semibold">{{ $payment->party_name }}</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Date</label>
                        <div class="fw-semibold">{{ $payment->payment_date_show }}</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Mode</label>
                        <div class="fw-semibold">{{ $payment->paymentMode?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted">Amount</label>
                        <div class="fw-semibold">{{ money_value($payment->amount) }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Reference Number</label>
                        <div class="fw-semibold">{{ $payment->reference_number ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Notes</label>
                        <div class="fw-semibold">{{ $payment->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Linked Bills</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Bill Type</th>
                                <th>Bill Number</th>
                                <th>Bill Date</th>
                                <th>Bill Amount</th>
                                <th>Allocated Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($allocationRows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['bill_type'] }}</td>
                                    <td>{{ $row['bill_number'] }}</td>
                                    <td>{{ $row['bill_date'] }}</td>
                                    <td>{{ money_value($row['bill_amount']) }}</td>
                                    <td>{{ money_value($row['allocated_amount']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No bills linked. This payment is saved as on-account / advance.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
