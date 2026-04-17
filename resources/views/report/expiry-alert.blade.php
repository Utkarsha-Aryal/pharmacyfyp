@extends('layouts.main')

@section('title')
    Expiry Alert
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Expiry Alert Report</h5>
                <p class="mb-0 text-muted">Batch wise expiry list for tracking products expiring inside the next 3 to 6 months.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.expiry-alert', request()->query()) }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.reports.expiry-alert.print', request()->query()) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Expired</p>
                        <h3 class="summary-card-value">{{ $expiredCount }}</h3>
                        <span class="summary-card-note">Already crossed expiry date.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Near Expiry</p>
                        <h3 class="summary-card-value">{{ $nearCount }}</h3>
                        <span class="summary-card-note">Inside the selected future date window.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Safe Batch</p>
                        <h3 class="summary-card-value">{{ $safeCount }}</h3>
                        <span class="summary-card-note">Expiry date is still safe.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Expiry Filter</div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-6 d-flex gap-2 flex-wrap">
                        <input type="hidden" name="window" id="expiryWindow" value="{{ $filters['window'] ?? '6m' }}">
                        <button type="button" class="btn btn-outline-primary js-expiry-window" data-window="3m">
                            <i class="fa-solid fa-clock"></i> 3 Months
                        </button>
                        <button type="button" class="btn btn-outline-primary js-expiry-window" data-window="6m">
                            <i class="fa-solid fa-calendar"></i> 6 Months
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter"></i> Apply
                        </button>
                        <a href="{{ route('admin.report.expiry', ['window' => '6m']) }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Expiry List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="expiryAlertTable" class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Supplier</th>
                                <th>Expiry</th>
                                <th>Days Left</th>
                                <th>Qty</th>
                                <th>Location</th>
                                <th>Status</th>
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
            window.expiryAlertTable = window.initServerSideDataTable({
                selector: '#expiryAlertTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'product' },
                    { data: 'batch' },
                    { data: 'supplier' },
                    { data: 'expiry' },
                    { data: 'days_left' },
                    { data: 'qty' },
                    { data: 'location' },
                    { data: 'status' },
                ],
                ajaxUrl: '{{ route('admin.report.expiry.list') }}',
                ajaxData: function (request) {
                    request.date_from = $('input[name="date_from"]').val() || '';
                    request.date_to = $('input[name="date_to"]').val() || '';
                    request.window = $('#expiryWindow').val() || '6m';
                }
            });

            $(document).on('click', '.js-expiry-window', function () {
                var windowType = $(this).data('window');
                var today = new Date();
                var dateTo = new Date(today.getTime());

                if (windowType === '3m') {
                    dateTo.setMonth(dateTo.getMonth() + 3);
                } else {
                    dateTo.setMonth(dateTo.getMonth() + 6);
                }

                var formatDate = function (value) {
                    var year = value.getFullYear();
                    var month = String(value.getMonth() + 1).padStart(2, '0');
                    var day = String(value.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                };

                $('#expiryWindow').val(windowType);
                $('input[name="date_from"]').val(formatDate(today));
                $('input[name="date_to"]').val(formatDate(dateTo));
                $(this).closest('form').trigger('submit');
            });

            $(document).on('change', 'input[name="date_from"], input[name="date_to"], #expiryWindow', function () {
                if (window.expiryAlertTable) {
                    window.expiryAlertTable.draw();
                }
            });
        });
    </script>
@endsection
