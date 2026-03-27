@extends('layouts.main')

@section('title')
    Purchase Bills
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase Bills</h5>
                <p class="mb-0 text-muted">Direct supplier bills that create stock immediately after save.</p>
            </div>
            <div class="d-flex my-xl-auto right-content gap-2">
                <a href="{{ route('admin.export.purchase', ['supplier_id' => $selectedSupplier, 'order_status' => $selectedOrderStatus]) }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Bill Entry
                </a>
            </div>
        </div>

        <div class="alert alert-primary border-0 soft-toolbar-btn mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            Use <strong>Purchase Bills</strong> when stock is already received now. Use <strong>Purchase Orders</strong> when you want approval and receiving steps separately.
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
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm icon-only-btn" title="Apply Filter" aria-label="Apply Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.purchase') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal fade" id="purchaseBillViewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Purchase Bill Summary</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Reference No</label>
                                <div class="fw-semibold" id="purchaseBillReferenceView">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Invoice No</label>
                                <div class="fw-semibold" id="purchaseBillInvoiceView">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Supplier</label>
                                <div class="fw-semibold" id="purchaseBillSupplierView">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Purchase Date</label>
                                <div class="fw-semibold" id="purchaseBillDateView">-</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Items</label>
                                <div class="fw-semibold" id="purchaseBillItemsView">-</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Status</label>
                                <div class="fw-semibold" id="purchaseBillStatusView">-</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Total</label>
                                <div class="fw-semibold" id="purchaseBillTotalView">-</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Due</label>
                                <div class="fw-semibold" id="purchaseBillDueView">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted">Remarks</label>
                                <div class="fw-semibold" id="purchaseBillRemarksView">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> New Bill Entry
                        </a>
                    </div>
                </div>
            </div>
        </div>

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
                <div class="card-title">Purchase Bill List</div>
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
                                <th>Action</th>
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
            var purchaseBillViewModalElement = document.getElementById('purchaseBillViewModal');
            var purchaseBillViewModal = purchaseBillViewModalElement ? new bootstrap.Modal(purchaseBillViewModalElement) : null;

            $(document).on('click', '.viewPurchaseBillBtn', function () {
                $('#purchaseBillReferenceView').text($(this).data('reference'));
                $('#purchaseBillInvoiceView').text($(this).data('invoice'));
                $('#purchaseBillSupplierView').text($(this).data('supplier'));
                $('#purchaseBillDateView').text($(this).data('date'));
                $('#purchaseBillItemsView').text($(this).data('items'));
                $('#purchaseBillStatusView').text($(this).data('status'));
                $('#purchaseBillTotalView').text($(this).data('total'));
                $('#purchaseBillDueView').text($(this).data('due'));
                $('#purchaseBillRemarksView').text($(this).data('remarks'));

                if (purchaseBillViewModal) {
                    purchaseBillViewModal.show();
                }
            });
        });
    </script>
@endsection
