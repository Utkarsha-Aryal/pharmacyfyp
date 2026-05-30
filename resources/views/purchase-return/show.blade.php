@extends('layouts.main')

@section('title')
    Purchase Return Details
@endsection

@section('main-content')
    @php
        $totalQty = (float) $purchaseReturn->items->sum('return_qty');
        $grossReturn = (float) $purchaseReturn->items->sum(function ($item) {
            return (float) $item->return_qty * (float) $item->rate;
        });
        $discountTotal = (float) $purchaseReturn->items->sum('discount_amount');
        $netReturn = (float) $purchaseReturn->items->sum(function ($item) {
            return (float) $item->effective_return_amount;
        });
        $returnModeLabel = $purchaseReturn->purchase_id ? 'By Purchase Bill' : 'By Product & Batch';
        $returnModeClass = $purchaseReturn->purchase_id ? 'bg-primary' : 'bg-warning text-dark';
        $purchaseBillLabel = $purchaseReturn->purchase?->reference?->reference_no ?: ($purchaseReturn->purchase_id ? ('PUR-' . $purchaseReturn->purchase_id) : 'Manual / Unknown Bill');
    @endphp

    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase Return Details</h5>
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

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Supplier</p>
                        <h3 class="summary-card-value fs-18">{{ $purchaseReturn->supplier?->supplier_name ?? '-' }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Return Mode</p>
                        <h3 class="summary-card-value fs-18"><span class="badge {{ $returnModeClass }}">{{ $returnModeLabel }}</span></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Items Returned</p>
                        <h3 class="summary-card-value">{{ number_format($totalQty, 0) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Net Return</p>
                        <h3 class="summary-card-value">{{ money_value($netReturn) }}</h3>
                    </div>
                </div>
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
                        <div class="fw-semibold"><span class="badge bg-light text-dark border">{{ $purchaseBillLabel }}</span></div>
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
                    <table class="table table-bordered align-middle purchase-item-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Return Qty</th>
                                <th>Rate</th>
                                <th>Discount %</th>
                                <th>Discount Amt</th>
                                <th>Net Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseReturn->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $item->batch?->batch_number ?? '-' }}</span></td>
                                    <td><span class="badge bg-info text-dark">{{ number_format((float) $item->return_qty, 0) }}</span></td>
                                    <td>{{ money_value($item->rate) }}</td>
                                    <td>{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                                    <td>{{ money_value($item->discount_amount ?? 0) }}</td>
                                    <td>{{ money_value($item->effective_net_rate) }}</td>
                                    <td>{{ money_value($item->effective_return_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-end">Total Return Qty</th>
                                <th>{{ number_format($totalQty, 0) }}</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Gross Return</th>
                                <th>{{ money_value($grossReturn) }}</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Total Discount</th>
                                <th>{{ money_value($discountTotal) }}</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Net Return</th>
                                <th>{{ money_value($netReturn) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
