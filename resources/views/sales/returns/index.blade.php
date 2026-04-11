@extends('layouts.main')

@section('title')
    Manage Sales Return
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Manage Sales Return</h5>
                <p class="mb-0 text-muted">Review sales return records and refund amounts.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Return Count</p>
                        <h3 class="mb-1">{{ $summary['count'] }}</h3>
                        <span class="text-muted small">Total sales return records.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Refund Total</p>
                        <h3 class="mb-1">{{ money_value($summary['refund_total']) }}</h3>
                        <span class="text-muted small">Refund amount in the selected filter.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">This Month</p>
                        <h3 class="mb-1">{{ money_value($summary['this_month']) }}</h3>
                        <span class="text-muted small">Refund amount for this month.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Today</p>
                        <h3 class="mb-1">{{ $summary['today'] }}</h3>
                        <span class="text-muted small">Returns recorded today.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.sales.returns.index') }}" method="GET" class="card custom-card filter-card sales-return-filter mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Party</label>
                        <select name="customer_id" class="form-select js-select2" data-placeholder="All party" data-allow-clear="1">
                            <option value="">All Party</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select js-select2" data-placeholder="All product" data-allow-clear="1">
                            <option value="">All Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-xl-2">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                            <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Sales Return List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesReturnTable" class="table table-bordered table-sm align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Party</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Refund</th>
                                <th>Reason</th>
                                <th style="width: 110px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            window.salesReturnTable = window.initServerSideDataTable({
                selector: '#salesReturnTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'invoice' },
                    { data: 'customer' },
                    { data: 'product' },
                    { data: 'qty' },
                    { data: 'refund' },
                    { data: 'reason' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.sales.returns.list') }}',
                ajaxData: function (request) {
                    request.customer_id = $('[name="customer_id"]').val() || '';
                    request.product_id = $('[name="product_id"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.sales-return-filter select, .sales-return-filter input', function () {
                if (window.salesReturnTable) {
                    window.salesReturnTable.draw();
                }
            });
        });
    </script>
@endsection
