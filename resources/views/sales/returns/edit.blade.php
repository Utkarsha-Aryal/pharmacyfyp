@extends('layouts.main')

@section('title')
    Edit Sales Return
@endsection

@section('body-class', 'workspace-form-page')

@php
    $selectedRefundStatus = old('refund_status', $salesReturn?->refund_status ?? 'paid');
    $selectedPaymentModeId = old('payment_mode_id', $salesReturn?->payment_mode_id ?? $selectedInvoice?->payment_mode_id ?? '');
    $selectedReturnMode = old('return_mode', 'invoice');
    $selectedCustomer = $selectedInvoice?->customer;
    $selectedProduct = $salesReturn?->product;
    $itemOptionMap = collect($salesReturnItemOptions ?? [])->keyBy('id');
    $returnRows = old('items', [[
        'sales_invoice_item_id' => $salesReturn?->sales_invoice_item_id,
        'quantity' => $salesReturn?->quantity,
        'unit_price' => $salesReturn?->unit_price,
        'discount_percent' => $salesReturn?->discount_percent,
        'discount_amount' => $salesReturn?->discount_amount,
        'net_unit_price' => $salesReturn?->net_unit_price,
        'refund_amount' => $salesReturn?->refund_amount,
    ]]);
@endphp

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Edit Sales Return</h5>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.sales.returns.update', $salesReturn) }}" method="POST" id="salesReturnEditForm">
            @csrf

            <div class="card custom-card mb-4">
                <div class="card-header">
                    <div class="card-title">Return Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Return Mode</label>
                            <div class="d-flex flex-wrap gap-3 pt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="return_mode" id="salesReturnModeInvoice" value="invoice" @checked($selectedReturnMode === 'invoice')>
                                    <label class="form-check-label" for="salesReturnModeInvoice">By Invoice</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="return_mode" id="salesReturnModeCustomerProduct" value="customer_product" @checked($selectedReturnMode === 'customer_product')>
                                    <label class="form-check-label" for="salesReturnModeCustomerProduct">By Customer / Product</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 sales-return-invoice-field">
                            <label class="form-label">Sales Invoice</label>
                            <select
                                name="sales_invoice_id"
                                id="salesReturnInvoiceSelect"
                                class="form-select"
                                data-placeholder="Search invoice"
                                required
                            >
                                @if ($selectedInvoiceOption)
                                    <option
                                        value="{{ $selectedInvoiceOption['id'] }}"
                                        data-reference="{{ $selectedInvoiceOption['reference'] }}"
                                        data-customer-name="{{ $selectedInvoiceOption['customer_name'] }}"
                                        data-invoice-date="{{ $selectedInvoiceOption['invoice_date'] }}"
                                        data-payment-mode-id="{{ $selectedInvoiceOption['payment_mode_id'] }}"
                                        data-payment-mode-name="{{ $selectedInvoiceOption['payment_mode_name'] }}"
                                        selected
                                    >
                                        {{ $selectedInvoiceOption['text'] }}
                                    </option>
                                @else
                                    <option value=""></option>
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-4 d-none sales-return-customer-field">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="salesReturnCustomerSelect" class="form-select" data-placeholder="Search customer">
                                @if ($selectedCustomer)
                                    <option value="{{ $selectedCustomer->id }}" selected>{{ $selectedCustomer->name }}</option>
                                @else
                                    <option value=""></option>
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-4 d-none sales-return-customer-field">
                            <label class="form-label">Product</label>
                            <select name="product_id" id="salesReturnProductSelect" class="form-select" data-placeholder="All products">
                                @if ($selectedProduct)
                                    <option value="{{ $selectedProduct->id }}" selected>{{ $selectedProduct->display_name }}</option>
                                @else
                                    <option value=""></option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Return Date</label>
                            <input type="date" name="return_date" class="form-control" value="{{ old('return_date', $salesReturn?->return_date ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Refund Status</label>
                            <select name="refund_status" id="salesReturnStatusSelect" class="form-select js-select2" data-placeholder="Select refund status" required>
                                <option value="paid" @selected($selectedRefundStatus === 'paid')>Paid</option>
                                <option value="pending" @selected($selectedRefundStatus === 'pending')>Pending Credit</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode_id" id="salesReturnPaymentModeSelect" class="form-select js-select2" data-placeholder="Select payment mode">
                                <option value="">Select payment mode</option>
                                @foreach ($paymentModes as $mode)
                                    <option value="{{ $mode->id }}" @selected((int) $selectedPaymentModeId === (int) $mode->id)>{{ $mode->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" value="{{ old('reason', $salesReturn?->reason ?? '') }}" placeholder="Customer return / damage / expiry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes', $salesReturn?->notes ?? '') }}" placeholder="Short note for staff or audit">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="small text-muted text-uppercase mb-1">Invoice</div>
                                <div class="fw-semibold" id="salesReturnInvoiceSummary">
                                    {{ $selectedInvoice?->reference ?: 'No invoice selected' }}
                                </div>
                                <div class="small text-muted" id="salesReturnInvoiceMeta">
                                    @if ($selectedInvoice)
                                        {{ $selectedInvoice->customer?->name ?: 'Walk-in Customer' }} | {{ $selectedInvoice->invoice_date_show }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="small text-muted text-uppercase mb-1">Total Return Qty</div>
                                <div class="fw-semibold" id="salesReturnQtyTotal">0</div>
                                <div class="small text-muted">Combined quantity from all return rows.</div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="small text-muted text-uppercase mb-1">Total Refund</div>
                                <div class="fw-semibold" id="salesReturnRefundTotal">{{ money_value(0) }}</div>
                                <div class="small text-muted">Total refund across all return rows.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div>
                        <div class="card-title">Return Items</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="loadSalesReturnItems">
                            <i class="fa-solid fa-file-invoice"></i> Load Remaining Items
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="addSalesReturnRow">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle purchase-item-table" id="salesReturnItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">S.No</th>
                                    <th>Invoice Item</th>
                                    <th style="width: 150px;">Batch</th>
                                    <th style="width: 110px;">Remaining</th>
                                    <th style="width: 110px;">Qty</th>
                                    <th style="width: 130px;">Unit Price</th>
                                    <th style="width: 120px;">Discount %</th>
                                    <th style="width: 130px;">Discount Amt</th>
                                    <th style="width: 120px;">Net Rate</th>
                                    <th style="width: 130px;">Refund Amt</th>
                                    <th style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody data-next-index="{{ count($returnRows) }}">
                                @foreach ($returnRows as $index => $row)
                                    @php
                                        $rowItemOption = $itemOptionMap->get((int) ($row['sales_invoice_item_id'] ?? 0));
                                        $rowRemainingQty = (float) ($rowItemOption['remaining_qty'] ?? 0);
                                    @endphp
                                    <tr data-pricing-mode="percent" data-remaining-qty="{{ $rowRemainingQty }}">
                                        <td class="sales-return-row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <select name="items[{{ $index }}][sales_invoice_item_id]" class="form-select sales-return-item-select" data-placeholder="Search invoice item" required>
                                                <option value=""></option>
                                                @if ($rowItemOption)
                                                    <option
                                                        value="{{ $rowItemOption['id'] }}"
                                                        data-product-name="{{ $rowItemOption['product_name'] }}"
                                                        data-batch-number="{{ $rowItemOption['batch_number'] }}"
                                                        data-remaining-qty="{{ $rowItemOption['remaining_qty'] }}"
                                                        data-discount-percent="{{ $rowItemOption['discount_percent'] }}"
                                                        data-net-rate="{{ $rowItemOption['net_rate'] }}"
                                                        data-unit-price="{{ $rowItemOption['unit_price'] }}"
                                                        data-per-unit-discount="{{ $rowItemOption['per_unit_discount'] }}"
                                                        data-pricing-note="{{ $rowItemOption['original_pricing_note'] }}"
                                                        selected
                                                    >
                                                        {{ $rowItemOption['text'] }}
                                                    </option>
                                                @endif
                                            </select>
                                            <small class="text-muted d-block mt-1 sales-return-item-note"></small>
                                            <small class="text-muted d-block sales-return-pricing-note">{{ $rowItemOption['original_pricing_note'] ?? '' }}</small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border sales-return-batch-label">{{ $rowItemOption['batch_number'] ?? '-' }}</span></td>
                                        <td><span class="badge bg-secondary sales-return-remaining-label">{{ number_format($rowRemainingQty, 0) }}</span></td>
                                        <td><input type="number" min="1" step="1" name="items[{{ $index }}][quantity]" class="form-control sales-return-qty-input" value="{{ $row['quantity'] ?? '' }}"></td>
                                        <td><input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]" class="form-control sales-return-unit-price-input" value="{{ $row['unit_price'] ?? '' }}"></td>
                                        <td><input type="number" min="0" max="100" step="0.01" name="items[{{ $index }}][discount_percent]" class="form-control sales-return-discount-input" value="{{ $row['discount_percent'] ?? '' }}"></td>
                                        <td><input type="number" min="0" step="0.01" name="items[{{ $index }}][discount_amount]" class="form-control sales-return-discount-amount-input" value="{{ $row['discount_amount'] ?? '' }}"></td>
                                        <td><input type="number" min="0" step="0.01" name="items[{{ $index }}][net_unit_price]" class="form-control sales-return-net-rate-input" value="{{ $row['net_unit_price'] ?? '' }}"></td>
                                        <td><input type="number" min="0" step="0.01" name="items[{{ $index }}][refund_amount]" class="form-control sales-return-refund-input" value="{{ $row['refund_amount'] ?? '' }}"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger removeSalesReturnRow table-action-btn">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="9" class="text-end fw-semibold">Gross Refund</th>
                                    <th><input type="text" id="salesReturnGrossTotal" class="form-control" value="0.00" readonly></th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="9" class="text-end fw-semibold">Total Discount</th>
                                    <th><input type="text" id="salesReturnDiscountTotal" class="form-control" value="0.00" readonly></th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="9" class="text-end fw-semibold">Net Refund</th>
                                    <th><input type="text" id="salesReturnTableRefundTotal" class="form-control" value="0.00" readonly></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <template id="salesReturnItemTemplate">
                        <tr data-pricing-mode="percent">
                            <td class="sales-return-row-number">__ROW__</td>
                            <td>
                                <select name="items[__INDEX__][sales_invoice_item_id]" class="form-select sales-return-item-select" data-placeholder="Search invoice item" required>
                                    <option value=""></option>
                                </select>
                                <small class="text-muted d-block mt-1 sales-return-item-note"></small>
                                <small class="text-muted d-block sales-return-pricing-note"></small>
                            </td>
                            <td><span class="badge bg-light text-dark border sales-return-batch-label">-</span></td>
                            <td><span class="badge bg-secondary sales-return-remaining-label">0</span></td>
                            <td><input type="number" min="1" step="1" name="items[__INDEX__][quantity]" class="form-control sales-return-qty-input" value=""></td>
                            <td><input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" class="form-control sales-return-unit-price-input" value=""></td>
                            <td><input type="number" min="0" max="100" step="0.01" name="items[__INDEX__][discount_percent]" class="form-control sales-return-discount-input" value=""></td>
                            <td><input type="number" min="0" step="0.01" name="items[__INDEX__][discount_amount]" class="form-control sales-return-discount-amount-input" value=""></td>
                            <td><input type="number" min="0" step="0.01" name="items[__INDEX__][net_unit_price]" class="form-control sales-return-net-rate-input" value=""></td>
                            <td><input type="number" min="0" step="0.01" name="items[__INDEX__][refund_amount]" class="form-control sales-return-refund-input" value=""></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger removeSalesReturnRow table-action-btn">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" form="salesReturnDeleteForm" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Sales Return
                    </button>
                </div>
            </div>
        </form>

        <form id="salesReturnDeleteForm" action="{{ route('admin.sales.returns.delete', $salesReturn) }}" method="POST" class="d-none js-confirm-submit" data-confirm-title="Delete sales return?" data-confirm-text="This will remove the return and take the stock back out of inventory." data-confirm-button="Yes, delete it">
            @csrf
        </form>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            var $modeInputs = $('input[name="return_mode"]');
            var $invoiceSelect = $('#salesReturnInvoiceSelect');
            var $customerSelect = $('#salesReturnCustomerSelect');
            var $productSelect = $('#salesReturnProductSelect');
            var $statusSelect = $('#salesReturnStatusSelect');
            var $paymentModeSelect = $('#salesReturnPaymentModeSelect');
            var $tableBody = $('#salesReturnItemsTable tbody');
            var $template = $('#salesReturnItemTemplate');
            var editingReturnId = '{{ $salesReturn->id }}';
            var itemOptionsUrl = '{{ route('admin.sales.returns.item-options') }}';

            function currentReturnMode() {
                return $modeInputs.filter(':checked').val() || 'invoice';
            }

            function currency(value) {
                return '{{ currency_symbol() }} ' + (parseFloat(value || 0) || 0).toFixed(2);
            }

            function safeNumber(value) {
                var parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function currentInvoiceData() {
                var $selected = $invoiceSelect.find('option:selected');

                if (!$selected.length || !$selected.val()) {
                    return null;
                }

                return {
                    id: $selected.val(),
                    reference: $selected.data('reference'),
                    customer_name: $selected.data('customerName'),
                    invoice_date: $selected.data('invoiceDate'),
                    payment_mode_id: $selected.data('paymentModeId'),
                    payment_mode_name: $selected.data('paymentModeName')
                };
            }

            function updateInvoiceSummary(data) {
                if (currentReturnMode() === 'customer_product') {
                    var customerName = $customerSelect.find('option:selected').text() || 'No customer selected';
                    var productName = $productSelect.find('option:selected').text() || 'All products';

                    $('#salesReturnInvoiceSummary').text(customerName);
                    $('#salesReturnInvoiceMeta').text('Return by customer/product | ' + productName);
                    return;
                }

                if (!data || !data.id) {
                    $('#salesReturnInvoiceSummary').text('No invoice selected');
                    $('#salesReturnInvoiceMeta').text('-');
                    return;
                }

                $('#salesReturnInvoiceSummary').text(data.reference || 'Selected invoice');
                $('#salesReturnInvoiceMeta').text((data.customer_name || 'Walk-in Customer') + ' | ' + (data.invoice_date || ''));
            }

            function sourceReady() {
                return currentReturnMode() === 'invoice'
                    ? Boolean($invoiceSelect.val())
                    : Boolean($customerSelect.val());
            }

            function updateModeState(resetTable) {
                var isInvoiceMode = currentReturnMode() === 'invoice';

                $('.sales-return-invoice-field').toggleClass('d-none', !isInvoiceMode);
                $('.sales-return-customer-field').toggleClass('d-none', isInvoiceMode);
                $invoiceSelect.prop('disabled', !isInvoiceMode).prop('required', isInvoiceMode);
                $customerSelect.prop('disabled', isInvoiceMode).prop('required', !isInvoiceMode);
                $productSelect.prop('disabled', isInvoiceMode);
                $invoiceSelect.add($customerSelect).add($productSelect).trigger('change.select2');
                $('#loadSalesReturnItems, #addSalesReturnRow').prop('disabled', !sourceReady());
                if (resetTable) {
                    resetRows();
                }

                updateInvoiceSummary(currentInvoiceData());
            }

            function syncRefundStatusState(prefillFromInvoice) {
                var isPaid = ($statusSelect.val() || 'paid') === 'paid';

                $paymentModeSelect.prop('disabled', !isPaid).trigger('change.select2');

                if (!isPaid) {
                    return;
                }

                if (prefillFromInvoice) {
                    var invoiceData = currentInvoiceData();
                    if (invoiceData && invoiceData.payment_mode_id && !$paymentModeSelect.val()) {
                        $paymentModeSelect.val(String(invoiceData.payment_mode_id)).trigger('change');
                    }
                }

            }

            function updateRowNumbers() {
                $tableBody.find('tr').each(function (index) {
                    $(this).find('.sales-return-row-number').text(index + 1);
                });
            }

            function updateTotals() {
                var totalQty = 0;
                var grossRefund = 0;
                var discountTotal = 0;
                var totalRefund = 0;

                $tableBody.find('tr').each(function () {
                    var qty = safeNumber($(this).find('.sales-return-qty-input').val());
                    var unitPrice = safeNumber($(this).find('.sales-return-unit-price-input').val());
                    totalQty += qty;
                    grossRefund += qty * unitPrice;
                    discountTotal += safeNumber($(this).find('.sales-return-discount-amount-input').val());
                    totalRefund += safeNumber($(this).find('.sales-return-refund-input').val());
                });

                $('#salesReturnQtyTotal').text(totalQty.toFixed(0));
                $('#salesReturnRefundTotal').text(currency(totalRefund));
                $('#salesReturnGrossTotal').val(grossRefund.toFixed(2));
                $('#salesReturnDiscountTotal').val(discountTotal.toFixed(2));
                $('#salesReturnTableRefundTotal').val(totalRefund.toFixed(2));
            }

            function resetRow($row) {
                $row.attr('data-pricing-mode', 'percent');
                $row.removeAttr('data-remaining-qty');
                $row.find('.sales-return-batch-label').text('-');
                $row.find('.sales-return-remaining-label').text('0');
                $row.find('.sales-return-item-note').text('');
                $row.find('.sales-return-pricing-note').text('');
                $row.find('.sales-return-qty-input, .sales-return-unit-price-input, .sales-return-discount-input, .sales-return-discount-amount-input, .sales-return-net-rate-input, .sales-return-refund-input').val('');
                updateTotals();
            }

            function setRowInputValue($row, selector, value, decimals, preserveEditing) {
                var $input = $row.find(selector);

                if (!$input.length) {
                    return;
                }

                if (preserveEditing && document.activeElement === $input.get(0)) {
                    return;
                }

                $input.val(Number(value || 0).toFixed(decimals));
            }

            function recalculateRow($row, mode, options) {
                options = options || {};
                var remainingQty = safeNumber($row.attr('data-remaining-qty'));
                var qty = safeNumber($row.find('.sales-return-qty-input').val());
                var unitPrice = safeNumber($row.find('.sales-return-unit-price-input').val());
                var discountPercent = safeNumber($row.find('.sales-return-discount-input').val());
                var discountAmount = safeNumber($row.find('.sales-return-discount-amount-input').val());
                var netRate = safeNumber($row.find('.sales-return-net-rate-input').val());
                var refundAmount = safeNumber($row.find('.sales-return-refund-input').val());

                if (remainingQty > 0 && qty > remainingQty) {
                    qty = remainingQty;
                    $row.find('.sales-return-qty-input').val(qty.toFixed(0));
                }

                if (qty < 0) {
                    qty = 0;
                    $row.find('.sales-return-qty-input').val('0');
                }

                if (mode === 'refund') {
                    refundAmount = Math.max(0, Math.min(qty * unitPrice, refundAmount));
                    netRate = qty > 0 ? refundAmount / qty : 0;
                    netRate = Math.max(0, Math.min(unitPrice, netRate));
                    discountAmount = Math.max(0, (unitPrice - netRate) * qty);
                    discountPercent = unitPrice > 0 ? ((unitPrice - netRate) / unitPrice) * 100 : 0;
                    refundAmount = qty * netRate;
                } else if (mode === 'amount') {
                    var perUnitDiscount = qty > 0 ? discountAmount / qty : 0;
                    netRate = Math.max(0, unitPrice - perUnitDiscount);
                    discountPercent = unitPrice > 0 ? ((unitPrice - netRate) / unitPrice) * 100 : 0;
                } else if (mode === 'net') {
                    netRate = Math.max(0, Math.min(unitPrice, netRate));
                    discountAmount = Math.max(0, (unitPrice - netRate) * qty);
                    discountPercent = unitPrice > 0 ? ((unitPrice - netRate) / unitPrice) * 100 : 0;
                } else {
                    discountPercent = Math.max(0, Math.min(100, discountPercent));
                    netRate = Math.max(0, unitPrice - ((unitPrice * discountPercent) / 100));
                    discountAmount = Math.max(0, (unitPrice - netRate) * qty);
                }

                $row.attr('data-pricing-mode', mode);
                setRowInputValue($row, '.sales-return-qty-input', qty, 0, options.preserveEditing);
                setRowInputValue($row, '.sales-return-unit-price-input', unitPrice, 2, options.preserveEditing);
                setRowInputValue($row, '.sales-return-discount-input', discountPercent, 2, options.preserveEditing);
                setRowInputValue($row, '.sales-return-discount-amount-input', discountAmount, 2, options.preserveEditing);
                setRowInputValue($row, '.sales-return-net-rate-input', netRate, 2, options.preserveEditing);
                setRowInputValue($row, '.sales-return-refund-input', qty * netRate, 2, options.preserveEditing);
                updateTotals();
            }

            function initItemSelect($select) {
                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $select.select2({
                    width: '100%',
                    placeholder: $select.data('placeholder') || 'Search invoice item',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: itemOptionsUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                sales_invoice_id: currentReturnMode() === 'invoice' ? ($invoiceSelect.val() || '') : '',
                                customer_id: currentReturnMode() === 'customer_product' ? ($customerSelect.val() || '') : '',
                                product_id: currentReturnMode() === 'customer_product' ? ($productSelect.val() || '') : '',
                                sales_return_id: editingReturnId
                            };
                        },
                        processResults: function (data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }

            function buildItemDataFromOption($selected) {
                return {
                    id: $selected.val(),
                    product_name: $selected.data('productName'),
                    batch_number: $selected.data('batchNumber'),
                    remaining_qty: $selected.data('remainingQty'),
                    discount_percent: $selected.data('discountPercent'),
                    original_pricing_note: $selected.data('pricingNote'),
                    net_rate: $selected.data('netRate'),
                    unit_price: $selected.data('unitPrice'),
                    text: $selected.text()
                };
            }

            function applyItemData($row, data, preserveExistingPricing) {
                var remainingQty = safeNumber(data.remaining_qty || 0);

                $row.attr('data-remaining-qty', remainingQty);
                $row.find('.sales-return-batch-label').text(data.batch_number || '-');
                $row.find('.sales-return-remaining-label').text(remainingQty.toFixed(0));
                $row.find('.sales-return-item-note').text((data.invoice_reference ? data.invoice_reference + ' | ' : '') + (data.product_name || 'Selected item') + ' | Batch ' + (data.batch_number || '-') + ' | Remaining ' + remainingQty.toFixed(0));
                $row.find('.sales-return-pricing-note').text(data.original_pricing_note || '');
                $row.find('.sales-return-qty-input').attr('max', remainingQty);

                if (!preserveExistingPricing || !$row.find('.sales-return-qty-input').val()) {
                    $row.find('.sales-return-qty-input').val(remainingQty > 0 ? Math.min(1, remainingQty) : '');
                }

                if (!preserveExistingPricing || !$row.find('.sales-return-unit-price-input').val()) {
                    $row.find('.sales-return-unit-price-input').val(safeNumber(data.unit_price || 0).toFixed(2));
                }

                if (!preserveExistingPricing || !$row.find('.sales-return-discount-input').val()) {
                    $row.find('.sales-return-discount-input').val(safeNumber(data.discount_percent || 0).toFixed(2));
                }

                if (!preserveExistingPricing || !$row.find('.sales-return-net-rate-input').val()) {
                    $row.find('.sales-return-net-rate-input').val(safeNumber(data.net_rate || 0).toFixed(2));
                }

                if (!preserveExistingPricing || !$row.find('.sales-return-discount-amount-input').val()) {
                    $row.find('.sales-return-discount-amount-input').val('0.00');
                }

                recalculateRow($row, $row.attr('data-pricing-mode') || 'percent');
            }

            function attachItemOption($row, data) {
                var $select = $row.find('.sales-return-item-select');
                var option = new Option(data.text || '', data.id, true, true);
                $(option)
                    .attr('data-product-name', data.product_name || '')
                    .attr('data-batch-number', data.batch_number || '')
                    .attr('data-remaining-qty', data.remaining_qty || 0)
                    .attr('data-discount-percent', data.discount_percent || 0)
                    .attr('data-pricing-note', data.original_pricing_note || '')
                    .attr('data-net-rate', data.net_rate || 0)
                    .attr('data-unit-price', data.unit_price || 0);

                $select.find('option[value="' + String(data.id) + '"]').remove();
                $select.append(option).trigger('change');
                applyItemData($row, data, false);
            }

            function selectedItemIds() {
                return $tableBody.find('.sales-return-item-select').map(function () {
                    return String($(this).val() || '');
                }).get().filter(Boolean);
            }

            function hydrateRowFromSelection($row) {
                var $selected = $row.find('.sales-return-item-select option:selected');

                if (!$selected.length || !$selected.val()) {
                    resetRow($row);
                    return;
                }
                applyItemData($row, buildItemDataFromOption($selected), true);
            }

            function addRow() {
                var nextIndex = parseInt($tableBody.data('next-index') || $tableBody.children().length || 1, 10);
                var html = $template.html()
                    .replace(/__INDEX__/g, nextIndex)
                    .replace(/__ROW__/g, nextIndex + 1);

                $tableBody.append(html);
                $tableBody.data('next-index', nextIndex + 1);
                initItemSelect($tableBody.find('tr:last .sales-return-item-select'));
                updateRowNumbers();
                updateTotals();
            }

            function resetRows() {
                $tableBody.find('tr:gt(0)').remove();
                var $firstRow = $tableBody.find('tr').first();

                if ($firstRow.length) {
                    resetRow($firstRow);
                    $firstRow.find('.sales-return-item-select').empty().append(new Option('', '', false, false)).trigger('change');
                }

                updateRowNumbers();
                updateTotals();
            }

            $invoiceSelect.select2({
                width: '100%',
                placeholder: $invoiceSelect.data('placeholder') || 'Search invoice',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('admin.sales.returns.invoice-options') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $customerSelect.select2({
                width: '100%',
                placeholder: $customerSelect.data('placeholder') || 'Search customer',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('admin.sales.customer-options') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $productSelect.select2({
                width: '100%',
                placeholder: $productSelect.data('placeholder') || 'All products',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('admin.sales.product-options') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $tableBody.find('.sales-return-item-select').each(function () {
                initItemSelect($(this));
                hydrateRowFromSelection($(this).closest('tr'));
            });

            $(document).on('click', '#addSalesReturnRow', function () {
                if (!sourceReady()) {
                    return;
                }

                addRow();
            });

            $(document).on('click', '.removeSalesReturnRow', function () {
                var $row = $(this).closest('tr');

                if ($tableBody.find('tr').length === 1) {
                    resetRow($row);
                    $row.find('.sales-return-item-select').val(null).trigger('change');
                    return;
                }

                $row.remove();
                updateRowNumbers();
                updateTotals();
            });

            $(document).on('select2:select', '.sales-return-item-select', function (event) {
                applyItemData($(this).closest('tr'), event.params.data || {}, false);
            });

            $(document).on('change', '.sales-return-item-select', function () {
                if ($(this).val()) {
                    return;
                }

                resetRow($(this).closest('tr'));
            });

            function salesReturnPricingMode($input, $row) {
                var mode = $row.attr('data-pricing-mode') || 'percent';

                if ($input.hasClass('sales-return-discount-input')) {
                    mode = 'percent';
                } else if ($input.hasClass('sales-return-discount-amount-input')) {
                    mode = 'amount';
                } else if ($input.hasClass('sales-return-net-rate-input')) {
                    mode = 'net';
                } else if ($input.hasClass('sales-return-refund-input')) {
                    mode = 'refund';
                }

                return mode;
            }

            $(document).on('input', '.sales-return-qty-input, .sales-return-unit-price-input, .sales-return-discount-input, .sales-return-discount-amount-input, .sales-return-net-rate-input, .sales-return-refund-input', function () {
                var $row = $(this).closest('tr');
                var mode = salesReturnPricingMode($(this), $row);

                recalculateRow($row, mode, { preserveEditing: true });
            });

            $(document).on('change blur', '.sales-return-qty-input, .sales-return-unit-price-input, .sales-return-discount-input, .sales-return-discount-amount-input, .sales-return-net-rate-input, .sales-return-refund-input', function () {
                var $row = $(this).closest('tr');
                var mode = salesReturnPricingMode($(this), $row);

                recalculateRow($row, mode);
            });

            $(document).on('click', '#loadSalesReturnItems', function () {
                if (!sourceReady()) {
                    return;
                }

                $.get(itemOptionsUrl, {
                    sales_invoice_id: currentReturnMode() === 'invoice' ? ($invoiceSelect.val() || '') : '',
                    customer_id: currentReturnMode() === 'customer_product' ? ($customerSelect.val() || '') : '',
                    product_id: currentReturnMode() === 'customer_product' ? ($productSelect.val() || '') : '',
                    sales_return_id: editingReturnId
                }, function (response) {
                    var existingIds = selectedItemIds();
                    var availableItems = (response.results || []).filter(function (item) {
                        return existingIds.indexOf(String(item.id)) === -1;
                    });

                    if (!availableItems.length) {
                        if (window.showNotification) {
                            window.showNotification('No more returnable invoice items left to load.', 'info');
                        }
                        return;
                    }

                    availableItems.forEach(function (item) {
                        var $row = $tableBody.find('tr').filter(function () {
                            return !$(this).find('.sales-return-item-select').val();
                        }).first();

                        if (!$row.length) {
                            addRow();
                            $row = $tableBody.find('tr').last();
                        }

                        attachItemOption($row, item);
                    });

                    updateRowNumbers();
                });
            });

            $invoiceSelect.on('select2:select', function (event) {
                updateInvoiceSummary(event.params.data || null);
                syncRefundStatusState(true);
                resetRows();
                updateModeState(false);
            });

            $invoiceSelect.on('change', function () {
                if (!$(this).val()) {
                    updateInvoiceSummary(null);
                    resetRows();
                }

                syncRefundStatusState(true);
                updateModeState(false);
            });

            $modeInputs.on('change', function () {
                updateModeState(true);
            });

            $customerSelect.on('change select2:select select2:clear', function () {
                resetRows();
                updateModeState(false);
            });

            $productSelect.on('change select2:select select2:clear', function () {
                resetRows();
                updateModeState(false);
            });

            $statusSelect.on('change', function () {
                syncRefundStatusState(true);
            });

            updateModeState(false);
            updateInvoiceSummary(currentInvoiceData());
            syncRefundStatusState(false);
            updateTotals();
        });
    </script>
@endsection
