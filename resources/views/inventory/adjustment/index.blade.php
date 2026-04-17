@extends('layouts.main')

@section('title')
    Stock Adjustment
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Stock Adjustment</h5>
                <p class="mb-0 text-muted">Adjust stock for damage, returns, expiry or manual correction.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <button type="button" class="btn btn-primary openAdjustmentModal">
                    <i class="fa-solid fa-plus"></i> Add Adjustment
                </button>
            </div>
        </div>

        <div class="modal fade" id="adjustmentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('admin.inventory.adjustments.store') }}" method="POST" id="adjustmentForm">
                        @csrf
                        <input type="hidden" name="id" id="adjustmentId">
                        <div class="modal-header">
                            <h5 class="modal-title">Adjustment Form</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" id="adjustmentProductSelect" class="form-select js-select2" required>
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) old('product_id', $selectedProductId) === (string) $product->id)>{{ $product->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Batch</label>
                                    <select name="batch_id" id="adjustmentBatchSelect" class="form-select js-select2" required>
                                        <option value="">Select Batch</option>
                                        @foreach ($batches as $batch)
                                            <option
                                                value="{{ $batch->id }}"
                                                data-product-id="{{ $batch->product_id }}"
                                                @selected((string) old('batch_id', $selectedBatchId) === (string) $batch->id)
                                            >
                                                {{ $batch->product?->display_name ?? '-' }} - {{ $batch->batch_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Adjustment Type</label>
                                    <select name="adjustment_type" id="adjustmentTypeSelect" class="form-select js-select2" required>
                                        @foreach ($adjustmentTypes as $type)
                                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" id="adjustmentQuantityInput" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Reason</label>
                                    <input type="text" name="reason" id="adjustmentReasonInput" class="form-control" placeholder="Write short reason">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="adjustmentSubmitBtn">
                                <i class="fa-solid fa-save"></i> Save Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Recent Adjustments</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="stockAdjustmentTable" class="table table-bordered align-middle w-100">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Reason</th>
                                <th>Adjusted By</th>
                                <th>Date</th>
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
            window.stockAdjustmentTable = window.initServerSideDataTable({
                selector: '#stockAdjustmentTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'product' },
                    { data: 'batch' },
                    { data: 'type' },
                    { data: 'quantity' },
                    { data: 'reason' },
                    { data: 'adjusted_by' },
                    { data: 'date' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.inventory.adjustments.list') }}',
            });

            var adjustmentModalElement = document.getElementById('adjustmentModal');
            var adjustmentModal = adjustmentModalElement ? new bootstrap.Modal(adjustmentModalElement) : null;
            var $productSelect = $('#adjustmentProductSelect');
            var $batchSelect = $('#adjustmentBatchSelect');
            var $typeSelect = $('#adjustmentTypeSelect');
            var $quantityInput = $('#adjustmentQuantityInput');
            var $reasonInput = $('#adjustmentReasonInput');
            var $adjustmentId = $('#adjustmentId');
            var $submitBtn = $('#adjustmentSubmitBtn');

            function resetAdjustmentForm() {
                $('#adjustmentForm')[0].reset();
                $adjustmentId.val('');
                $submitBtn.html('<i class="fa-solid fa-save"></i> Save Adjustment');
                $quantityInput.val(1);
                $typeSelect.val('add').trigger('change');
            }

            // keep batch dropdown matched with the selected product so wrong pair is not picked by mistake
            function syncAdjustmentBatchOptions() {
                var selectedProductId = $productSelect.val() || '';
                var keepCurrentBatch = false;

                $batchSelect.find('option').each(function () {
                    var $option = $(this);
                    var optionProductId = String($option.data('productId') || '');

                    if (!$option.val()) {
                        $option.prop('hidden', false);
                        return;
                    }

                    var shouldShow = selectedProductId === '' || optionProductId === String(selectedProductId);
                    $option.prop('hidden', !shouldShow);
                    $option.prop('disabled', !shouldShow);

                    if (shouldShow && $option.is(':selected')) {
                        keepCurrentBatch = true;
                    }
                });

                if (!keepCurrentBatch) {
                    $batchSelect.val('');
                }

                $batchSelect.trigger('change.select2');
            }

            $(document).on('click', '.openAdjustmentModal', function () {
                resetAdjustmentForm();
                syncAdjustmentBatchOptions();
                if (adjustmentModal) {
                    adjustmentModal.show();
                }
            });

            $(document).on('click', '.editAdjustmentBtn', function () {
                resetAdjustmentForm();
                $adjustmentId.val($(this).data('id'));
                $productSelect.val(String($(this).data('product_id'))).trigger('change');
                $batchSelect.val(String($(this).data('batch_id'))).trigger('change');
                $typeSelect.val(String($(this).data('adjustment_type'))).trigger('change');
                $quantityInput.val($(this).data('quantity'));
                $reasonInput.val($(this).data('reason'));
                $submitBtn.html('<i class="fa-solid fa-save"></i> Update Adjustment');

                if (adjustmentModal) {
                    adjustmentModal.show();
                }
            });

            $(document).on('hidden.bs.modal', '#adjustmentModal', function () {
                resetAdjustmentForm();
                syncAdjustmentBatchOptions();
            });

            $productSelect.on('change', syncAdjustmentBatchOptions);
            syncAdjustmentBatchOptions();
        });
    </script>
@endsection
