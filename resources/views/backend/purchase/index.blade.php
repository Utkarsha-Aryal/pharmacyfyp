@extends('backend.layouts.main')

@section('title')
    Purchase
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase History</h5>
                <p class="mb-0 text-muted">Received stock, supplier history and payment summary.</p>
            </div>
            <div class="d-flex my-xl-auto right-content gap-2">
                <a href="{{ route('admin.export.purchase', ['supplier_id' => $selectedSupplier, 'order_status' => $selectedOrderStatus]) }}" class="btn btn-outline-primary">
                    <i class="fa fa-download"></i> Excel
                </a>
                <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Purchase
                </a>
            </div>
        </div>

        <form action="{{ route('admin.purchase') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier Filter</label>
                        <select name="supplier_id" class="form-select js-select2" data-placeholder="All supplier" data-allow-clear="1">
                            <option value="">All Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $selectedSupplier === (string) $supplier->id)>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Order Status</label>
                        <select name="order_status" class="form-select js-select2" data-placeholder="All status" data-allow-clear="1">
                            <option value="">All Status</option>
                            <option value="pending" @selected($selectedOrderStatus === 'pending')>Pending</option>
                            <option value="approved" @selected($selectedOrderStatus === 'approved')>Approved</option>
                            <option value="received" @selected($selectedOrderStatus === 'received')>Received</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Show History</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.purchase') }}" class="btn btn-outline-secondary w-100">Reset Filter</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Total Purchase</p>
                        <h3 class="summary-card-value">{{ $purchaseCount }}</h3>
                        <span class="summary-card-note">Saved purchase bill count.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Grand Total</p>
                        <h3 class="summary-card-value">{{ number_format((float) $purchaseTotal, 2) }}</h3>
                        <span class="summary-card-note">Total received amount.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Paid Amount</p>
                        <h3 class="summary-card-value">{{ number_format((float) $paidTotal, 2) }}</h3>
                        <span class="summary-card-note">Payment already cleared.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Due Amount</p>
                        <h3 class="summary-card-value">{{ number_format((float) $dueTotal, 2) }}</h3>
                        <span class="summary-card-note">Basic payable tracking.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Purchase List</div>
            </div>
            <div class="card-body">
                <input type="hidden" id="current_supplier_id" value="{{ $selectedSupplier }}">
                <input type="hidden" id="current_order_status" value="{{ $selectedOrderStatus }}">
                <div class="table-responsive">
                    <table id="purchaseTable" class="table table-bordered text-nowrap w-100" data-list-url="{{ route('admin.purchase.list') }}">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Reference No</th>
                                <th>Invoice No</th>
                                <th>Supplier</th>
                                <th>Items</th>
                                <th>Grand Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Order Status</th>
                                <th>Purchase Date</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
