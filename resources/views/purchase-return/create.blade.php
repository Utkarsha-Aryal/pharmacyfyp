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
                <p class="mb-0 text-muted">Create a supplier return by purchase bill or by product and batch.</p>
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
                        <label class="form-label">Return Mode</label>
                        <div class="d-flex flex-wrap gap-3 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_mode" id="purchaseReturnModeBill" value="bill" checked>
                                <label class="form-check-label" for="purchaseReturnModeBill">By Purchase Bill</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_mode" id="purchaseReturnModeProduct" value="product">
                                <label class="form-check-label" for="purchaseReturnModeProduct">By Product &amp; Batch</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Short note">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Bill</label>
                        <select name="purchase_id" id="purchaseReturnPurchase" class="form-select js-select2" data-placeholder="Select purchase">
                            <option value="">Select purchase</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="purchaseReturnProduct" class="form-select js-select2" data-placeholder="Select product">
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div class="alert alert-light border mb-0 small text-muted" id="purchaseReturnModeHelp">
                            Use purchase bill mode when the bill is known. Switch to product and batch mode when staff only knows the supplier and medicine.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="purchaseReturnItemsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Batch Selection</th>
                                <th>Original Qty</th>
                                <th>Already Returned</th>
                                <th>Max Returnable</th>
                                <th>Return Qty</th>
                                <th>Rate</th>
                                <th>Discount %</th>
                                <th>Discount Amt</th>
                                <th>Net Rate</th>
                                <th>Return Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="11" class="text-center text-muted">Select purchase bill to load returnable items.</td>
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
            var $supplierSelect = $('#purchaseReturnSupplier');
            var $purchaseSelect = $('#purchaseReturnPurchase');
            var $productSelect = $('#purchaseReturnProduct');
            var $modeInputs = $('input[name="return_mode"]');
            var $modeHelp = $('#purchaseReturnModeHelp');
            var $itemsTbody = $('#purchaseReturnItemsTable tbody');

            function escapeHtml(text) {
                return String(text ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function resetItemsTable(message) {
                $itemsTbody.html('<tr><td colspan="11" class="text-center text-muted">' + (message || 'Select purchase bill to load returnable items.') + '</td></tr>');
            }

            function safeNumber(value) {
                var parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function recalculateRow($row, source) {
                var qty = safeNumber($row.find('.purchase-return-qty-input').val());
                var rate = safeNumber($row.find('.purchase-return-rate-input').val());
                var discountPercent = safeNumber($row.find('.purchase-return-discount-input').val());
                var discountAmount = safeNumber($row.find('.purchase-return-discount-amount-input').val());
                var netRate = safeNumber($row.find('.purchase-return-net-rate-input').val());

                if (source === 'amount') {
                    var amountDiscountPerUnit = qty > 0 ? discountAmount / qty : 0;
                    netRate = Math.max(0, rate - amountDiscountPerUnit);
                    discountPercent = rate > 0 ? ((rate - netRate) / rate) * 100 : 0;
                } else if (source === 'net') {
                    netRate = Math.max(0, Math.min(rate, netRate));
                    discountAmount = Math.max(0, (rate - netRate) * qty);
                    discountPercent = rate > 0 ? ((rate - netRate) / rate) * 100 : 0;
                } else {
                    discountPercent = Math.max(0, Math.min(100, discountPercent));
                    netRate = Math.max(0, rate - ((rate * discountPercent) / 100));
                    discountAmount = Math.max(0, (rate - netRate) * qty);
                }

                $row.find('.purchase-return-rate-input').val(rate.toFixed(2));
                $row.find('.purchase-return-discount-input').val(discountPercent.toFixed(2));
                $row.find('.purchase-return-discount-amount-input').val(discountAmount.toFixed(2));
                $row.find('.purchase-return-net-rate-input').val(netRate.toFixed(2));
                $row.find('.purchase-return-amount-input').val((qty * netRate).toFixed(2));
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

            function renderRows(rows) {
                $itemsTbody.empty();

                if (!(rows || []).length) {
                    resetItemsTable('No returnable items found.');
                    return;
                }

                rows.forEach(function (row, index) {
                    $itemsTbody.append(
                        '<tr data-sync-mode="percent">' +
                            '<td>' + escapeHtml(row.product_name) +
                                '<input type="hidden" name="items[' + index + '][purchase_item_id]" value="' + escapeHtml(row.purchase_item_id || '') + '">' +
                                '<input type="hidden" name="items[' + index + '][product_id]" value="' + escapeHtml(row.product_id) + '">' +
                            '</td>' +
                            '<td>' + buildBatchSelect(row, index) + '</td>' +
                            '<td>' + escapeHtml(row.original_qty) + '</td>' +
                            '<td>' + escapeHtml(row.already_returned) + '</td>' +
                            '<td>' + escapeHtml(row.max_returnable) + '</td>' +
                            '<td><input type="number" name="items[' + index + '][return_qty]" class="form-control purchase-return-qty-input" min="0" max="' + escapeHtml(row.max_returnable) + '" value="0" ' + (!(row.batch_options || []).length ? 'disabled' : '') + '></td>' +
                            '<td><input type="number" name="items[' + index + '][rate]" class="form-control purchase-return-rate-input" min="0" step="0.01" value="' + escapeHtml(row.rate || 0) + '"></td>' +
                            '<td><input type="number" name="items[' + index + '][discount_percent]" class="form-control purchase-return-discount-input" min="0" max="100" step="0.01" value="' + escapeHtml(row.discount_percent || 0) + '"></td>' +
                            '<td><input type="number" name="items[' + index + '][discount_amount]" class="form-control purchase-return-discount-amount-input" min="0" step="0.01" value="' + escapeHtml(row.discount_amount || 0) + '"></td>' +
                            '<td><input type="number" name="items[' + index + '][net_rate]" class="form-control purchase-return-net-rate-input" min="0" step="0.01" value="' + escapeHtml(row.net_rate || row.rate || 0) + '"></td>' +
                            '<td><input type="text" name="items[' + index + '][return_amount]" class="form-control purchase-return-amount-input" value="' + escapeHtml(row.return_amount || 0) + '" readonly></td>' +
                        '</tr>'
                    );
                });

                $itemsTbody.find('tr').each(function () {
                    recalculateRow($(this), $(this).data('syncMode') || 'percent');
                });
            }

            function loadSupplierPurchases(supplierId) {
                $purchaseSelect.empty().append('<option value="">Select purchase</option>').trigger('change');

                if (!supplierId) {
                    return;
                }

                $.get('{{ route('admin.purchase-returns.get-purchases') }}', { supplier_id: supplierId }, function (response) {
                    (response || []).forEach(function (row) {
                        $purchaseSelect.append(new Option(row.text, row.id, false, false));
                    });
                });
            }

            function refreshReturnRows() {
                var supplierId = $supplierSelect.val();
                var purchaseId = $purchaseSelect.val();
                var productId = $productSelect.val();
                var noBillMode = $modeInputs.filter(':checked').val() === 'product';

                if (!supplierId) {
                    resetItemsTable('Select supplier first to load returnable rows.');
                    return;
                }

                if (noBillMode) {
                    if (!productId) {
                        resetItemsTable('Select product to load supplier batch rows.');
                        return;
                    }

                    $itemsTbody.html('<tr><td colspan="11" class="text-center text-muted">Loading supplier batch rows...</td></tr>');
                    $.get('{{ route('admin.purchase-returns.get-batches') }}', { supplier_id: supplierId, product_id: productId }, function (response) {
                        renderRows(response || []);
                    });
                    return;
                }

                if (!purchaseId) {
                    resetItemsTable('Select purchase bill to load returnable items.');
                    return;
                }

                $itemsTbody.html('<tr><td colspan="11" class="text-center text-muted">Loading items...</td></tr>');
                $.get('{{ route('admin.purchase-returns.get-items') }}', { purchase_id: purchaseId }, function (response) {
                    renderRows(response || []);
                });
            }

            function syncNoBillState() {
                var noBillMode = $modeInputs.filter(':checked').val() === 'product';

                if (noBillMode) {
                    $purchaseSelect.val('').trigger('change');
                    $purchaseSelect.prop('disabled', true).prop('required', false);
                    $productSelect.prop('disabled', false);
                    $modeHelp.text('Product and batch mode is active. Pick the medicine first, then choose the supplier batch to return.');
                } else {
                    $purchaseSelect.prop('disabled', false);
                    $productSelect.val('').trigger('change');
                    $productSelect.prop('disabled', true);
                    $modeHelp.text('Purchase bill mode is active. Choose the bill first and the system will load only the rows from that bill.');
                }
            }

            $(document).on('change', '#purchaseReturnSupplier', function () {
                loadSupplierPurchases($(this).val());
                refreshReturnRows();
            });

            $(document).on('change', '#purchaseReturnPurchase', function () {
                refreshReturnRows();
            });

            $(document).on('change', 'input[name="return_mode"]', function () {
                syncNoBillState();
                refreshReturnRows();
            });

            $(document).on('change', '#purchaseReturnProduct', function () {
                refreshReturnRows();
            });

            $(document).on('change', '.purchase-return-batch-select', function () {
                var $select = $(this);
                var $badge = $select.closest('td').find('.purchase-return-batch-badge');
                var $option = $select.find('option:selected');
                var badgeClass = $option.data('badge-class') || 'bg-warning text-dark';
                var badgeLabel = $option.data('badge-label') || 'Choose a batch';

                $badge.attr('class', 'badge purchase-return-batch-badge ' + badgeClass).text(badgeLabel);
            });

            $(document).on('input', '.purchase-return-qty-input, .purchase-return-rate-input, .purchase-return-discount-input, .purchase-return-discount-amount-input, .purchase-return-net-rate-input', function () {
                var $row = $(this).closest('tr');
                var qty = parseInt($row.find('.purchase-return-qty-input').val() || '0', 10);
                var $select = $row.find('.purchase-return-batch-select');
                var mode = $row.data('syncMode') || 'percent';

                if ($(this).hasClass('purchase-return-discount-input')) {
                    mode = 'percent';
                } else if ($(this).hasClass('purchase-return-discount-amount-input')) {
                    mode = 'amount';
                } else if ($(this).hasClass('purchase-return-net-rate-input')) {
                    mode = 'net';
                }

                $row.attr('data-sync-mode', mode);
                recalculateRow($row, mode);

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

            syncNoBillState();
        });
    </script>
@endsection
