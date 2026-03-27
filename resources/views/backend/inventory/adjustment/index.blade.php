@extends('backend.layouts.main')

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
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Adjustment Form</div>
            </div>
            <form action="{{ route('admin.inventory.adjustments.store') }}" method="POST">
                @csrf
                <div class="card-body">
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
                            <select name="adjustment_type" class="form-select js-select2" required>
                                <option value="add">Add</option>
                                <option value="subtract">Subtract</option>
                                <option value="expired">Expired</option>
                                <option value="damaged">Damaged</option>
                                <option value="return">Return</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" placeholder="Write short reason">
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                </div>
            </form>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Recent Adjustments</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
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
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adjustments as $index => $adjustment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $adjustment->product?->display_name ?? '-' }}</td>
                                    <td>{{ $adjustment->batch?->batch_number ?? '-' }}</td>
                                    <td>{{ ucfirst($adjustment->adjustment_type) }}</td>
                                    <td>{{ $adjustment->quantity }}</td>
                                    <td>{{ $adjustment->reason ?: '-' }}</td>
                                    <td>{{ $adjustment->adjustedBy?->name ?? '-' }}</td>
                                    <td>{{ $adjustment->created_at?->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No adjustment data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            var $productSelect = $('#adjustmentProductSelect');
            var $batchSelect = $('#adjustmentBatchSelect');

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

            $productSelect.on('change', syncAdjustmentBatchOptions);
            syncAdjustmentBatchOptions();
        });
    </script>
@endsection
