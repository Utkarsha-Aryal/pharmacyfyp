@extends('layouts.main')

@section('title')
    Sales Return Details
@endsection

@section('main-content')
    @php
        $invoiceLabel = $salesReturn->invoice?->reference ?: ($salesReturn->sales_invoice_id ? ('Invoice #' . $salesReturn->sales_invoice_id) : '-');
        $customerLabel = $salesReturn->invoice?->customer?->name ?: 'Walk-in Customer';
        $quantity = (float) $salesReturn->quantity;
        $grossRefund = $quantity * (float) $salesReturn->effective_unit_price;
        $discountTotal = (float) $salesReturn->effective_discount_amount;
        $netRefund = (float) $salesReturn->refund_amount;
        $settlementLabel = (float) ($salesReturn->cash_refund_amount ?? 0) > 0
            ? ($salesReturn->payment_mode_label ?: 'Paid')
            : ((float) ($salesReturn->pending_credit_amount ?? 0) > 0 ? 'Pending Credit' : 'Adjusted');
    @endphp

    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Sales Return Details</h5>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                @if ($salesReturn->sales_invoice_id)
                    <a href="{{ route('admin.sales.show', $salesReturn->sales_invoice_id) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-invoice"></i> Open Invoice
                    </a>
                @endif
                <a href="{{ route('admin.sales.returns.edit', $salesReturn) }}" class="btn btn-outline-warning">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Return
                </a>
                <form action="{{ route('admin.sales.returns.delete', $salesReturn) }}" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete sales return?" data-confirm-text="This will remove the return and take the stock back out of inventory." data-confirm-button="Yes, delete it">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Customer</p>
                        <h3 class="summary-card-value fs-18">{{ $customerLabel }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Settlement</p>
                        <h3 class="summary-card-value fs-18">
                            <span class="badge {{ $salesReturn->refund_status_badge_class }}">{{ $settlementLabel }}</span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Return Qty</p>
                        <h3 class="summary-card-value">{{ number_format($quantity, 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Net Refund</p>
                        <h3 class="summary-card-value">{{ money_value($netRefund) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted">Invoice</label>
                        <div class="fw-semibold"><span class="badge bg-light text-dark border">{{ $invoiceLabel }}</span></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Return Date</label>
                        <div class="fw-semibold">{{ $salesReturn->return_date_show }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Payment Mode</label>
                        <div class="fw-semibold">{{ $salesReturn->payment_mode_label }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Created By</label>
                        <div class="fw-semibold">{{ $salesReturn->creator?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Reason</label>
                        <div class="fw-semibold">{{ $salesReturn->reason ?: '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Notes</label>
                        <div class="fw-semibold">{{ $salesReturn->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Returned Item</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle purchase-item-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Return Qty</th>
                                <th>Unit Price</th>
                                <th>Discount %</th>
                                <th>Discount Amt</th>
                                <th>Net Rate</th>
                                <th>Refund</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>{{ $salesReturn->product?->display_name ?? '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $salesReturn->batch?->batch_number ?? '-' }}</span></td>
                                <td><span class="badge bg-info text-dark">{{ number_format($quantity, 0) }}</span></td>
                                <td>{{ money_value($salesReturn->effective_unit_price) }}</td>
                                <td>{{ number_format((float) $salesReturn->effective_discount_percent, 2) }}%</td>
                                <td>{{ money_value($discountTotal) }}</td>
                                <td>{{ money_value($salesReturn->effective_net_unit_price) }}</td>
                                <td>{{ money_value($netRefund) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-end">Total Return Qty</th>
                                <th>{{ number_format($quantity, 0) }}</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">Gross Refund</th>
                                <th>{{ money_value($grossRefund) }}</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">Total Discount</th>
                                <th>{{ money_value($discountTotal) }}</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">Net Refund</th>
                                <th>{{ money_value($netRefund) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
