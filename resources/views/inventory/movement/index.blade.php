@extends('layouts.main')

@section('title')
    Case Movement
@endsection

@section('main-content')
    <div class="admin-page-wrap">
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Case Movement</h5>
                <p class="mb-0 text-muted">Check stock in, stock out, and source details.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Total Rows</p>
                        <h3 class="mb-1">{{ $summary['total_rows'] }}</h3>
                        <span class="text-muted small">Movement records in the selected filter.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Stock In</p>
                        <h3 class="mb-1">{{ $summary['total_in'] }}</h3>
                        <span class="text-muted small">Total inward quantity.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Stock Out</p>
                        <h3 class="mb-1">{{ $summary['total_out'] }}</h3>
                        <span class="text-muted small">Total outward quantity.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Net</p>
                        <h3 class="mb-1">{{ $summary['net'] }}</h3>
                        <span class="text-muted small">Stock in minus stock out.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.inventory.movements.index') }}" method="GET" class="card custom-card filter-card case-movement-filter mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select js-select2" data-placeholder="All companies" data-allow-clear="1">
                            <option value="">All Companies</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select js-select2" data-placeholder="All products" data-allow-clear="1">
                            <option value="">All Products</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Movement</label>
                        <select name="movement_type" class="form-select js-select2" data-placeholder="All types" data-allow-clear="1">
                            <option value="">All Types</option>
                            @foreach ($movementTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected(($filters['movement_type'] ?? '') === $typeKey)>{{ $typeLabel }}</option>
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
                    <div class="col-xl-12">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                            <a href="{{ route('admin.inventory.movements.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Case Movement</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="caseMovementTable" class="table table-bordered table-sm align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Movement</th>
                                <th>Qty</th>
                                <th>Flow</th>
                                <th>Reference</th>
                                <th>Notes</th>
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
            window.caseMovementTable = window.initServerSideDataTable({
                selector: '#caseMovementTable',
                pageLength: 15,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'product' },
                    { data: 'batch' },
                    { data: 'movement' },
                    { data: 'qty' },
                    { data: 'flow' },
                    { data: 'reference' },
                    { data: 'notes' },
                ],
                ajaxUrl: '{{ route('admin.inventory.movements.list') }}',
                ajaxData: function (request) {
                    request.company_id = $('[name="company_id"]').val() || '';
                    request.product_id = $('[name="product_id"]').val() || '';
                    request.movement_type = $('[name="movement_type"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', '.case-movement-filter select, .case-movement-filter input', function () {
                if (window.caseMovementTable) {
                    window.caseMovementTable.draw();
                }
            });
        });
    </script>
@endsection
