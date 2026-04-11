@extends('layouts.main')

@section('title')
    Purchase Returns
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase Returns</h5>
                <p class="mb-0 text-muted">Review supplier return entries by bill or by product and batch.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Purchase Return
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Return Count</p>
                        <h3 class="mb-1">{{ $summary['count'] }}</h3>
                        <span class="text-muted small">Total returns in the selected filter.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Manual Returns</p>
                        <h3 class="mb-1">{{ $summary['manual'] }}</h3>
                        <span class="text-muted small">Entries made without a purchase bill.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Returned Items</p>
                        <h3 class="mb-1">{{ $summary['items'] }}</h3>
                        <span class="text-muted small">Line items recorded in the filter.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">This Month</p>
                        <h3 class="mb-1">{{ $summary['this_month'] }}</h3>
                        <span class="text-muted small">Returns created this month.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.purchase-returns.index') }}" method="GET" class="card custom-card mb-4 purchase-return-filter">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select js-select2" data-placeholder="All suppliers" data-allow-clear="1">
                            <option value="">All Suppliers</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Return Mode</label>
                        <select name="return_mode" class="form-select js-select2" data-placeholder="All modes" data-allow-clear="1">
                            <option value="">All Modes</option>
                            <option value="bill" @selected(($filters['return_mode'] ?? '') === 'bill')>By Purchase Bill</option>
                            <option value="product" @selected(($filters['return_mode'] ?? '') === 'product')>By Product &amp; Batch</option>
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
                            <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Purchase Return List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="purchaseReturnTable" class="table table-bordered table-sm align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Mode</th>
                                <th>Purchase Bill</th>
                                <th>Items</th>
                                <th>Created By</th>
                                <th style="width: 140px;">Action</th>
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
            window.purchaseReturnTable = window.initServerSideDataTable({
                selector: '#purchaseReturnTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'supplier' },
                    { data: 'mode' },
                    { data: 'purchase_bill' },
                    { data: 'items' },
                    { data: 'created_by' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.purchase-returns.list') }}',
                ajaxData: function (request) {
                    request.supplier_id = $('[name="supplier_id"]').val() || '';
                    request.return_mode = $('[name="return_mode"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.purchase-return-filter select, .purchase-return-filter input', function () {
                if (window.purchaseReturnTable) {
                    window.purchaseReturnTable.draw();
                }
            });
        });
    </script>
@endsection
