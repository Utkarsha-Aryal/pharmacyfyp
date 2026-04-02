@extends('layouts.main')

@section('title')
    Receive Purchase Order
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Receive Goods</h5>
                <p class="mb-0 text-muted">
                    Enter batch numbers and expiry dates before the stock is added.
                    <span class="ms-2 text-muted">
                        <i class="fa-solid fa-keyboard me-1"></i>Ctrl+Enter finish receive
                    </span>
                </p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-orders.show', $order) }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.purchase-orders.receive.store', $order) }}" method="POST">
            @csrf
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Receive Items for {{ $order->reference }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">S.No</th>
                                    <th>Product</th>
                                    <th>Qty Ordered</th>
                                    <th>Qty Received</th>
                                    <th>Batch Number</th>
                                    <th>Expiry Date</th>
                                    <th>Manufacturing Date</th>
                                    <th>Storage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->product?->display_name ?? '-' }}</div>
                                            <small class="text-muted">{{ number_format((float) $item->unit_price, 2) }} per unit</small>
                                        </td>
                                        <td>{{ $item->quantity_ordered }}</td>
                                        <td>
                                            <input type="number" name="items[{{ $item->id }}][quantity_received]" class="form-control" min="1" value="{{ $item->quantity_ordered }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $item->id }}][batch_number]" class="form-control" value="{{ $item->batch_number ?: 'BATCH-' . str()->upper(str()->random(4)) }}" required>
                                        </td>
                                        <td>
                                            <input type="date" name="items[{{ $item->id }}][expiry_date]" class="form-control" value="{{ $item->expiry_date }}" required>
                                        </td>
                                        <td>
                                            <input type="date" name="items[{{ $item->id }}][manufacturing_date]" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $item->id }}][storage_location]" class="form-control" placeholder="Rack A-1">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Finish Receive
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
