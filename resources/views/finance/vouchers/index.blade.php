@extends('layouts.main')

@section('title')
    Vouchers
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Vouchers</h5>
                <p class="mb-0 text-muted">Create and manage manual finance vouchers from one list.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.finance.vouchers.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Voucher
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Voucher Count</p>
                        <h3 class="mb-1">{{ $summary['count'] }}</h3>
                        <span class="text-muted small">Total vouchers in the current filter.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Voucher Value</p>
                        <h3 class="mb-1">{{ money_value($summary['amount']) }}</h3>
                        <span class="text-muted small">Balanced debit value posted by voucher.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">This Month</p>
                        <h3 class="mb-1">{{ $summary['this_month'] }}</h3>
                        <span class="text-muted small">Vouchers posted in the current month.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Journal Vouchers</p>
                        <h3 class="mb-1">{{ $summary['journal'] }}</h3>
                        <span class="text-muted small">Journal entries in the selected filter.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.finance.vouchers.index') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Voucher Type</label>
                        <select name="voucher_type" class="form-select js-select2" data-placeholder="All voucher types" data-allow-clear="1">
                            <option value="">All Types</option>
                            @foreach ($voucherTypes as $voucherTypeKey => $voucherTypeLabel)
                                <option value="{{ $voucherTypeKey }}" @selected(($filters['voucher_type'] ?? '') === $voucherTypeKey)>{{ $voucherTypeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary mt-md-4">Apply Filter</button>
                        <a href="{{ route('admin.finance.vouchers.index') }}" class="btn btn-outline-secondary mt-md-4">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Voucher List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="voucherTable" class="table table-bordered table-sm align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Voucher</th>
                                <th>Date</th>
                                <th>Party</th>
                                <th>Entries</th>
                                <th>Amount</th>
                                <th>Created By</th>
                                <th style="width: 150px;">Action</th>
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
        $(function () {
            window.voucherTable = window.initServerSideDataTable({
                selector: '#voucherTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'voucher_no' },
                    { data: 'date' },
                    { data: 'party' },
                    { data: 'entries' },
                    { data: 'amount' },
                    { data: 'created_by' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.finance.vouchers.list') }}',
                ajaxData: function (request) {
                    request.voucher_type = $('[name="voucher_type"]').val() || '';
                    request.date_from = $('[name="date_from"]').val() || '';
                    request.date_to = $('[name="date_to"]').val() || '';
                }
            });

            $(document).on('change', 'form.filter-card select, form.filter-card input', function () {
                if (window.voucherTable) {
                    window.voucherTable.draw();
                }
            });
        });
    </script>
@endsection
