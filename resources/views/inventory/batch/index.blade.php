@extends('layouts.main')

@section('title')
    Batch List
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Batch List</h5>
                <p class="mb-0 text-muted">Batch-wise stock with expiry and storage location.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.inventory-batches', request()->query()) }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.inventory-batches-pdf', request()->query()) }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <button type="button" class="btn btn-primary addBatchBtn">
                    <i class="fa fa-plus"></i> Add Batch
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Total Batches</p>
                        <h3 class="summary-card-value">{{ $summary['total_batches'] }}</h3>
                        <span class="summary-card-note">All active batches.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Expired</p>
                        <h3 class="summary-card-value">{{ $summary['expired_batches'] }}</h3>
                        <span class="summary-card-note">Past expiry date.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Expiring Soon</p>
                        <h3 class="summary-card-value">{{ $summary['expiring_soon'] }}</h3>
                        <span class="summary-card-note">Inside next 30 days.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Stock Qty</p>
                        <h3 class="summary-card-value">{{ $summary['total_stock'] }}</h3>
                        <span class="summary-card-note">Available in batches.</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.inventory.batches.index') }}" method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select js-select2" data-placeholder="All product" data-allow-clear="1">
                            <option value="">All Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select js-select2" data-placeholder="All supplier" data-allow-clear="1">
                            <option value="">All Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expiry Status</label>
                        <select name="expiry_status" class="form-select js-select2" data-placeholder="All status" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="expired" @selected(($filters['expiry_status'] ?? '') === 'expired')>Expired</option>
                            <option value="7d" @selected(($filters['expiry_status'] ?? '') === '7d')>7 Days</option>
                            <option value="30d" @selected(($filters['expiry_status'] ?? '') === '30d')>30 Days</option>
                            <option value="60d" @selected(($filters['expiry_status'] ?? '') === '60d')>60 Days</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm icon-only-btn" title="Apply Filter" aria-label="Apply Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.inventory.batches.index') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset Filter" aria-label="Reset Filter">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal fade" id="batchModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('admin.inventory.batches.store') }}" method="POST" id="batchForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Manual Batch Entry</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <input type="hidden" name="id" id="batch_id" value="">
                                <div class="col-md-3">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" class="form-select js-select2" required>
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Supplier</label>
                                    <select name="supplier_id" class="form-select js-select2" required>
                                        <option value="">Select Supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Batch Number</label>
                                    <input type="text" name="batch_number" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Storage</label>
                                    <input type="text" name="storage_location" class="form-control" placeholder="Rack A-1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Manufacturing Date</label>
                                    <input type="date" name="manufacturing_date" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qty Received</label>
                                    <input type="number" name="quantity_received" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qty Available</label>
                                    <input type="number" name="quantity_available" class="form-control" min="0" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Purchase Price</label>
                                    <input type="number" name="purchase_price" class="form-control" min="0" step="0.01" value="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm icon-only-btn" id="resetBatchForm" title="Reset Form" aria-label="Reset Form">
                                <i class="fa-solid fa-rotate-right"></i>
                            </button>
                            <button type="submit" class="btn btn-primary" id="batchSaveBtn"><i class="fa fa-save"></i> Save Batch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Batch List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="inventoryBatchTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Product</th>
                                <th>Batch No</th>
                                <th>Supplier</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Qty</th>
                                <th>Storage</th>
                                <th>Status</th>
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
            window.inventoryBatchTable = window.initServerSideDataTable({
                selector: '#inventoryBatchTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'product' },
                    { data: 'batch_number' },
                    { data: 'supplier' },
                    { data: 'expiry_date' },
                    { data: 'days_remaining' },
                    { data: 'quantity' },
                    { data: 'storage' },
                    { data: 'status' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.inventory.batches.list') }}',
                ajaxData: function (request) {
                    request.product_id = $('[name="product_id"]').val() || '';
                    request.supplier_id = $('[name="supplier_id"]').val() || '';
                    request.expiry_status = $('[name="expiry_status"]').val() || '';
                }
            });

            var batchModalElement = document.getElementById('batchModal');
            var batchModal = batchModalElement ? new bootstrap.Modal(batchModalElement) : null;
            var batchForm = $('#batchForm');
            var batchIdInput = $('#batch_id');
            var saveButton = $('#batchSaveBtn');

            function resetBatchForm() {
                batchForm[0].reset();
                batchIdInput.val('');
                saveButton.html('<i class="fa fa-save"></i> Save Batch');
                batchForm.find('.js-select2').val('').trigger('change');
                batchForm.find('[name="quantity_received"]').val(1);
                batchForm.find('[name="quantity_available"]').val(1);
                batchForm.find('[name="purchase_price"]').val(0);
            }

            $(document).on('click', '.addBatchBtn', function () {
                resetBatchForm();
                if (batchModal) {
                    batchModal.show();
                }
            });

            $(document).on('click', '#resetBatchForm', function () {
                resetBatchForm();
            });

            $(document).on('hidden.bs.modal', '#batchModal', function () {
                resetBatchForm();
            });

            $(document).on('change', '.filter-card select, .filter-card input', function () {
                if (window.inventoryBatchTable) {
                    window.inventoryBatchTable.draw();
                }
            });

            $(document).on('click', '.editBatch', function () {
                batchIdInput.val($(this).data('id'));
                batchForm.find('[name="product_id"]').val($(this).data('product-id')).trigger('change');
                batchForm.find('[name="supplier_id"]').val($(this).data('supplier-id')).trigger('change');
                batchForm.find('[name="batch_number"]').val($(this).data('batch-number'));
                batchForm.find('[name="manufacturing_date"]').val($(this).data('manufacturing-date'));
                batchForm.find('[name="expiry_date"]').val($(this).data('expiry-date'));
                batchForm.find('[name="quantity_received"]').val($(this).data('quantity-received'));
                batchForm.find('[name="quantity_available"]').val($(this).data('quantity-available'));
                batchForm.find('[name="purchase_price"]').val($(this).data('purchase-price'));
                batchForm.find('[name="storage_location"]').val($(this).data('storage-location'));
                saveButton.html('<i class="fa fa-save"></i> Update Batch');
                if (batchModal) {
                    batchModal.show();
                }
            });
        });
    </script>
@endsection
