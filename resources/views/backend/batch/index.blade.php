@extends('backend.layouts.main')

@section('title')
    Batch History
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Batch History</h5>
                <p class="mb-0 text-muted">{{ $product->product_name }} batch wise stock movement from purchase entry.</p>
            </div>
            <div class="d-flex my-xl-auto right-content gap-2">
                <a href="{{ route('admin.export.batch', $product->slug) }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.product') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Product Name</p>
                        <h3 class="summary-card-text">{{ $product->product_name }}</h3>
                        <span class="summary-card-note">Master product record.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Active Batches</p>
                        <h3 class="summary-card-value">{{ $batchCount }}</h3>
                        <span class="summary-card-note">Total saved stock batch.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Stock Qty</p>
                        <h3 class="summary-card-value">{{ $stockQty }}</h3>
                        <span class="summary-card-note">Current total quantity in hand.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Batch List</div>
            </div>
            <div class="card-body">
                <input type="hidden" id="batch_product_id" value="{{ $product->id }}">
                <div class="table-responsive">
                    <table id="batchHistoryTable" class="table table-bordered text-nowrap w-100" data-list-url="{{ route('admin.batch.list') }}">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Batch No</th>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Purchase Date</th>
                                <th>Expiry</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
