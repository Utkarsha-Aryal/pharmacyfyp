@extends('layouts.main')

@section('title')
    Purchase Return Details
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase Return Details</h5>
                <p class="mb-0 text-muted">Review returned batch rows and print the debit note style PDF.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.purchase-returns.edit', $purchaseReturn) }}" class="btn btn-outline-warning">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Return
                </a>
                <a href="{{ route('admin.purchase-returns.print', $purchaseReturn) }}" target="_blank" class="btn btn-primary">
                    <i class="fa fa-print"></i> Print / PDF
                </a>
                <form action="{{ route('admin.purchase-returns.delete', $purchaseReturn) }}" method="POST" class="d-inline js-confirm-submit" data-confirm-title="Delete purchase return?" data-confirm-text="This will restore the stock back to inventory." data-confirm-button="Yes, delete it">
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
                        <label class="form-label text-muted">Supplier</label>
                        <div class="fw-semibold">{{ $purchaseReturn->supplier?->supplier_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Purchase Bill</label>
                        <div class="fw-semibold">{{ $purchaseReturn->purchase?->reference?->reference_no ?: ($purchaseReturn->purchase_id ? ('PUR-' . $purchaseReturn->purchase_id) : 'Manual / Unknown Bill') }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Return Date</label>
                        <div class="fw-semibold">{{ $purchaseReturn->return_date_show }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted">Created By</label>
                        <div class="fw-semibold">{{ $purchaseReturn->returnedBy?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted">Notes</label>
                        <div class="fw-semibold">{{ $purchaseReturn->notes ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Returned Items</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Return Qty</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Net Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalReturn = 0; @endphp
                            @foreach ($purchaseReturn->items as $index => $item)
                                @php
                                    $lineTotal = (float) $item->effective_return_amount;
                                    $totalReturn += $lineTotal;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                                    <td>{{ $item->batch?->batch_number ?? '-' }}</td>
                                    <td>{{ $item->return_qty }}</td>
                                    <td>{{ money_value($item->rate) }}</td>
                                    <td>{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                                    <td>{{ money_value($item->effective_net_rate) }}</td>
                                    <td>{{ money_value($lineTotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-end">Total Return Value</th>
                                <th>{{ money_value($totalReturn) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
