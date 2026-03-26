@extends('backend.layouts.main')

@section('title')
    Purchase Order Detail
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $order->reference }}</h5>
                <p class="mb-0 text-muted">Order detail, approval status and payment update.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                @if ($order->status === 'pending')
                    <form action="{{ route('admin.purchase-orders.approve', $order) }}" method="POST">
                        @csrf
                        <button class="btn btn-warning">Approve</button>
                    </form>
                @endif
                @if ($order->status === 'approved')
                    <a href="{{ route('admin.purchase-orders.receive', $order) }}" class="btn btn-success">Receive Goods</a>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Supplier</p>
                        <h3 class="summary-card-text">{{ $order->supplier?->supplier_name ?? '-' }}</h3>
                        <span class="summary-card-note">Ordered by {{ $order->orderedBy?->name ?? '-' }}.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Status</p>
                        <h3 class="summary-card-text">{{ $order->status_label }}</h3>
                        <span class="summary-card-note">Current workflow state.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Payment</p>
                        <h3 class="summary-card-text">{{ $order->payment_label }}</h3>
                        <span class="summary-card-note">Paid amount {{ number_format((float) $order->paid_amount, 2) }}.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Due</p>
                        <h3 class="summary-card-text">{{ number_format((float) $order->outstanding_amount, 2) }}</h3>
                        <span class="summary-card-note">Outstanding payable.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Order Items</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Product</th>
                                <th>Qty Ordered</th>
                                <th>Qty Received</th>
                                <th>Unit Price</th>
                                <th>Batch Number</th>
                                <th>Expiry Date</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                                    <td>{{ $item->quantity_ordered }}</td>
                                    <td>{{ $item->quantity_received }}</td>
                                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td>{{ $item->batch_number ?: '-' }}</td>
                                    <td>{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('M j, Y') : '-' }}</td>
                                    <td>{{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Payment Update</div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.purchase-orders.payment', $order) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select js-select2">
                            <option value="unpaid" @selected($order->payment_status === 'unpaid')>Unpaid</option>
                            <option value="partial" @selected($order->payment_status === 'partial')>Partial</option>
                            <option value="paid" @selected($order->payment_status === 'paid')>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Paid Amount</label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" value="{{ $order->paid_amount }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
