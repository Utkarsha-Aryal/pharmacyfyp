@extends('layouts.main')

@section('title')
    New Purchase Return
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">New Purchase Return</h5>
                <p class="mb-0 text-muted">Select supplier, purchase bill and batch rows to return stock back.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.purchase-returns.store') }}" method="POST" id="purchaseReturnForm" class="card custom-card">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Supplier</span>
                            <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add supplier" data-quick-modal="#quickSupplierModal" data-quick-target-select="#purchaseReturnSupplier">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </label>
                        <select name="supplier_id" id="purchaseReturnSupplier" class="form-select js-select2" data-placeholder="Select supplier" required>
                            <option value="">Select supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Bill</label>
                        <select name="purchase_id" id="purchaseReturnPurchase" class="form-select js-select2" data-placeholder="Select purchase" required>
                            <option value="">Select purchase</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Short note">
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="purchaseReturnItemsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Batch Selection</th>
                                <th>Original Qty</th>
                                <th>Already Returned</th>
                                <th>Max Returnable</th>
                                <th>Return Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Select purchase bill to load returnable items.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Purchase Return
                </button>
            </div>
        </form>

        @include('partials.quick-create-modals', [
            'showQuickSupplier' => auth()->user()->can('purchase.supplier'),
            'showQuickSupplierType' => auth()->user()->can('settings.manage'),
            'supplierTypes' => $supplierTypes ?? collect(),
        ])
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            $(document).on('change', '#purchaseReturnSupplier', function () {
                var supplierId = $(this).val();
                var $purchaseSelect = $('#purchaseReturnPurchase');
                $purchaseSelect.empty().append('<option value="">Select purchase</option>').trigger('change');
                $('#purchaseReturnItemsTable tbody').html('<tr><td colspan="6" class="text-center text-muted">Select purchase bill to load returnable items.</td></tr>');

                if (!supplierId) {
                    return;
                }

                $.get('{{ route('admin.purchase-returns.get-purchases') }}', { supplier_id: supplierId }, function (response) {
                    (response || []).forEach(function (row) {
                        $purchaseSelect.append(new Option(row.text, row.id, false, false));
                    });
                });
            });

            $(document).on('change', '#purchaseReturnPurchase', function () {
                var purchaseId = $(this).val();
                var tbody = $('#purchaseReturnItemsTable tbody');
                tbody.html('<tr><td colspan="6" class="text-center text-muted">Loading items...</td></tr>');

                if (!purchaseId) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">Select purchase bill to load returnable items.</td></tr>');
                    return;
                }

                $.get('{{ route('admin.purchase-returns.get-items') }}', { purchase_id: purchaseId }, function (response) {
                    tbody.empty();

                    if (!(response || []).length) {
                        tbody.html('<tr><td colspan="6" class="text-center text-muted">No returnable items found.</td></tr>');
                        return;
                    }

                    function escapeHtml(text) {
                        return String(text ?? '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function buildBatchSelect(row, index) {
                        if (!(row.batch_options || []).length) {
                            return '' +
                                '<div class="d-flex flex-column gap-1">' +
                                    '<span class="badge bg-danger">No returnable batch available</span>' +
                                    '<small class="text-muted">This row cannot be returned because the stock in the batch is already used.</small>' +
                                    '<input type="hidden" name="items[' + index + '][batch_id]" value="">' +
                                '</div>';
                        }

                        var options = '<option value="">Select batch</option>';

                        (row.batch_options || []).forEach(function (batch) {
                            var selected = String(row.selected_batch_id || '') === String(batch.id) ? 'selected' : '';
                            var disabled = batch.disabled ? 'disabled' : '';

                            options += '<option value="' + escapeHtml(batch.id) + '" data-badge-class="' + escapeHtml(batch.badge_class) + '" data-badge-label="' + escapeHtml(batch.badge_label) + '" ' + selected + ' ' + disabled + '>' + escapeHtml(batch.text) + '</option>';
                        });

                        return '' +
                            '<div class="d-flex flex-column gap-1">' +
                                '<select name="items[' + index + '][batch_id]" class="form-select form-select-sm purchase-return-batch-select">' +
                                    options +
                                '</select>' +
                                '<span class="badge purchase-return-batch-badge ' + escapeHtml(row.batch_badge_class || 'bg-warning text-dark') + '">' + escapeHtml(row.batch_badge_label || 'Choose a batch') + '</span>' +
                            '</div>';
                    }

                    response.forEach(function (row, index) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + row.product_name +
                                    '<input type="hidden" name="items[' + index + '][purchase_item_id]" value="' + row.purchase_item_id + '">' +
                                    '<input type="hidden" name="items[' + index + '][product_id]" value="' + row.product_id + '">' +
                                '</td>' +
                                '<td>' + buildBatchSelect(row, index) + '</td>' +
                                '<td>' + row.original_qty + '</td>' +
                                '<td>' + row.already_returned + '</td>' +
                                '<td>' + row.max_returnable + '</td>' +
                                '<td><input type="number" name="items[' + index + '][return_qty]" class="form-control purchase-return-qty-input" min="0" max="' + row.max_returnable + '" value="0" ' + (!(row.batch_options || []).length ? 'disabled' : '') + '></td>' +
                            '</tr>'
                        );
                    });
                });
            });

            $(document).on('change', '.purchase-return-batch-select', function () {
                var $select = $(this);
                var $badge = $select.closest('td').find('.purchase-return-batch-badge');
                var $option = $select.find('option:selected');
                var badgeClass = $option.data('badge-class') || 'bg-warning text-dark';
                var badgeLabel = $option.data('badge-label') || 'Choose a batch';

                $badge.attr('class', 'badge purchase-return-batch-badge ' + badgeClass).text(badgeLabel);
            });

            $(document).on('input', '.purchase-return-qty-input', function () {
                var $row = $(this).closest('tr');
                var qty = parseInt($(this).val() || '0', 10);
                var $select = $row.find('.purchase-return-batch-select');

                if ($select.length) {
                    if (qty > 0) {
                        $select.prop('disabled', false);
                    } else {
                        $select.prop('disabled', false);
                        if (!$select.val()) {
                            $select.removeClass('is-invalid');
                        }
                    }
                }
            });
        });
    </script>
@endsection
