@php
    $selectedInvoiceId = old('sales_invoice_id', $salesReturn?->sales_invoice_id ?? $selectedInvoiceOption['id'] ?? '');
    $selectedItemId = old('sales_invoice_item_id', $salesReturn?->sales_invoice_item_id ?? $selectedItemOption['id'] ?? '');
@endphp

<form action="{{ $formAction }}" method="POST" class="card custom-card" id="salesReturnForm">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-xl-6">
                <label class="form-label">Sales Invoice</label>
                <select
                    name="sales_invoice_id"
                    id="salesReturnInvoiceSelect"
                    class="form-select js-select2-ajax"
                    data-ajax-url="{{ route('admin.sales.returns.invoice-options') }}"
                    data-placeholder="Search invoice"
                    required
                >
                    @if ($selectedInvoiceId && $selectedInvoiceOption)
                        <option
                            value="{{ $selectedInvoiceOption['id'] }}"
                            data-reference="{{ $selectedInvoiceOption['reference'] }}"
                            data-customer-name="{{ $selectedInvoiceOption['customer_name'] }}"
                            data-invoice-date="{{ $selectedInvoiceOption['invoice_date'] }}"
                            selected
                        >
                            {{ $selectedInvoiceOption['text'] }}
                        </option>
                    @else
                        <option value=""></option>
                    @endif
                </select>
            </div>
            <div class="col-xl-6">
                <label class="form-label">Invoice Item</label>
                <select
                    name="sales_invoice_item_id"
                    id="salesReturnItemSelect"
                    class="form-select"
                    data-placeholder="Search invoice item"
                    @disabled(!$selectedInvoiceId)
                    required
                >
                    @if ($selectedItemId && $selectedItemOption)
                        <option
                            value="{{ $selectedItemOption['id'] }}"
                            data-product-name="{{ $selectedItemOption['product_name'] }}"
                            data-batch-number="{{ $selectedItemOption['batch_number'] }}"
                            data-remaining-qty="{{ $selectedItemOption['remaining_qty'] }}"
                            data-discount-percent="{{ $selectedItemOption['discount_percent'] }}"
                            data-net-rate="{{ $selectedItemOption['net_rate'] }}"
                            data-unit-price="{{ $selectedItemOption['unit_price'] }}"
                            data-per-unit-discount="{{ $selectedItemOption['per_unit_discount'] }}"
                            selected
                        >
                            {{ $selectedItemOption['text'] }}
                        </option>
                    @endif
                </select>
                <small class="text-muted d-block mt-1" id="salesReturnItemHint">Choose an invoice item to load the remaining quantity and net refund rate.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Return Date</label>
                <input type="date" name="return_date" class="form-control" value="{{ old('return_date', $salesReturn?->return_date ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity</label>
                <input type="number" min="1" step="1" name="quantity" id="salesReturnQtyInput" class="form-control" value="{{ old('quantity', $salesReturn?->quantity ?? '') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Refund Amount</label>
                <input type="number" min="0" step="0.01" name="refund_amount" id="salesReturnRefundInput" class="form-control" value="{{ old('refund_amount', $salesReturn?->refund_amount ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" value="{{ old('reason', $salesReturn?->reason ?? '') }}" placeholder="Customer return / damage / expiry">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional note for staff or audit">{{ old('notes', $salesReturn?->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card-body border-top">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small text-muted text-uppercase mb-1">Invoice</div>
                    <div class="fw-semibold" id="salesReturnInvoiceSummary">
                        {{ $selectedInvoice?->reference ?: 'No invoice selected' }}
                    </div>
                    <div class="small text-muted" id="salesReturnInvoiceMeta">
                        @if ($selectedInvoice)
                            {{ $selectedInvoice->customer?->name ?: 'Walk-in Customer' }} | {{ $selectedInvoice->invoice_date_show }}
                        @else
                            Search an invoice to continue.
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small text-muted text-uppercase mb-1">Item</div>
                    <div class="fw-semibold" id="salesReturnItemSummary">
                        {{ $selectedItemOption['product_name'] ?? 'No item selected' }}
                    </div>
                    <div class="small text-muted" id="salesReturnItemMeta">
                        @if ($selectedItemOption)
                            Batch {{ $selectedItemOption['batch_number'] }} | Remaining {{ number_format((float) $selectedItemOption['remaining_qty'], 0) }}
                        @else
                            Pick the invoice item to continue.
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small text-muted text-uppercase mb-1">Rate Snapshot</div>
                    <div class="fw-semibold" id="salesReturnRateSummary">
                        @if ($selectedItemOption)
                            Net {{ number_format((float) $selectedItemOption['net_rate'], 2) }}
                        @else
                            Net 0.00
                        @endif
                    </div>
                    <div class="small text-muted" id="salesReturnRateMeta">
                        @if ($selectedItemOption)
                            Discount {{ number_format((float) $selectedItemOption['discount_percent'], 2) }}% | Unit {{ number_format((float) $selectedItemOption['unit_price'], 2) }}
                        @else
                            Discount 0.00% | Unit 0.00
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            @if (!empty($showDeleteButton) && $salesReturn)
                <button type="submit" form="salesReturnDeleteForm" class="btn btn-outline-danger">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            @endif
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> {{ $submitLabel }}
        </button>
    </div>
</form>

@if (!empty($showDeleteButton) && $salesReturn)
    <form id="salesReturnDeleteForm" action="{{ route('admin.sales.returns.delete', $salesReturn) }}" method="POST" class="d-none js-confirm-submit" data-confirm-title="Delete sales return?" data-confirm-text="This will remove the return and take the stock back out of inventory." data-confirm-button="Yes, delete it">
        @csrf
    </form>
@endif

@section('script')
    <script>
        $(function () {
            var $invoiceSelect = $('#salesReturnInvoiceSelect');
            var $itemSelect = $('#salesReturnItemSelect');
            var $qtyInput = $('#salesReturnQtyInput');
            var $refundInput = $('#salesReturnRefundInput');
            var $itemHint = $('#salesReturnItemHint');
            var editingReturnId = '{{ $salesReturn?->id ?? '' }}';

            function currentInvoiceData() {
                var select2Data = $invoiceSelect.select2('data');
                var $selected = $invoiceSelect.find('option:selected');

                if (!$selected.length || !$selected.val()) {
                    return null;
                }

                var selectedData = {
                    id: $selected.val(),
                    text: $selected.text(),
                    reference: $selected.data('reference'),
                    customer_name: $selected.data('customerName'),
                    invoice_date: $selected.data('invoiceDate')
                };

                if (select2Data && select2Data.length && select2Data[0].id) {
                    return $.extend({}, selectedData, select2Data[0]);
                }

                return selectedData;
            }

            function currentItemData() {
                var select2Data = $itemSelect.select2('data');
                var $selected = $itemSelect.find('option:selected');
                if (!$selected.length || !$selected.val()) {
                    return null;
                }

                var selectedData = {
                    id: $selected.val(),
                    text: $selected.text(),
                    product_name: $selected.data('productName'),
                    batch_number: $selected.data('batchNumber'),
                    remaining_qty: parseFloat($selected.data('remainingQty') || 0),
                    discount_percent: parseFloat($selected.data('discountPercent') || 0),
                    net_rate: parseFloat($selected.data('netRate') || 0),
                    unit_price: parseFloat($selected.data('unitPrice') || 0),
                    per_unit_discount: parseFloat($selected.data('perUnitDiscount') || 0)
                };

                if (select2Data && select2Data.length && select2Data[0].id) {
                    return $.extend({}, selectedData, select2Data[0]);
                }

                return selectedData;
            }

            function updateInvoiceSummary(data) {
                if (!data || !data.id) {
                    $('#salesReturnInvoiceSummary').text('No invoice selected');
                    $('#salesReturnInvoiceMeta').text('Search an invoice to continue.');
                    return;
                }

                $('#salesReturnInvoiceSummary').text(data.reference || data.text || 'Selected invoice');
                $('#salesReturnInvoiceMeta').text((data.customer_name || 'Walk-in Customer') + ' | ' + (data.invoice_date || ''));
            }

            function updateItemSummary(data) {
                if (!data || !data.id) {
                    $('#salesReturnItemSummary').text('No item selected');
                    $('#salesReturnItemMeta').text('Pick the invoice item to continue.');
                    $('#salesReturnRateSummary').text('Net 0.00');
                    $('#salesReturnRateMeta').text('Discount 0.00% | Unit 0.00');
                    $itemHint.text('Choose an invoice item to load the remaining quantity and net refund rate.');
                    $qtyInput.attr('max', '');
                    return;
                }

                var remainingQty = parseFloat(data.remaining_qty || 0);
                var discountPercent = parseFloat(data.discount_percent || 0);
                var netRate = parseFloat(data.net_rate || 0);
                var unitPrice = parseFloat(data.unit_price || 0);

                $('#salesReturnItemSummary').text(data.product_name || data.text || 'Selected item');
                $('#salesReturnItemMeta').text('Batch ' + (data.batch_number || '-') + ' | Remaining ' + remainingQty.toFixed(0));
                $('#salesReturnRateSummary').text('Net ' + netRate.toFixed(2));
                $('#salesReturnRateMeta').text('Discount ' + discountPercent.toFixed(2) + '% | Unit ' + unitPrice.toFixed(2));
                $itemHint.text('Remaining qty: ' + remainingQty.toFixed(0) + ', discount: ' + discountPercent.toFixed(2) + '%, net rate: ' + netRate.toFixed(2));
                $qtyInput.attr('max', remainingQty);
            }

            function syncItemSelectState() {
                var hasInvoice = !!$invoiceSelect.val();
                $itemSelect.prop('disabled', !hasInvoice);
            }

            function syncRefund(preserveExistingQty) {
                var data = currentItemData();

                if (!data) {
                    updateItemSummary(null);
                    return;
                }

                updateItemSummary(data);

                var remainingQty = parseFloat(data.remaining_qty || 0);
                var qty = parseFloat($qtyInput.val() || 0);

                if (!preserveExistingQty) {
                    qty = remainingQty > 0 ? Math.min(1, remainingQty) : 0;
                    $qtyInput.val(qty > 0 ? qty : '');
                } else if (!qty) {
                    qty = remainingQty > 0 ? Math.min(1, remainingQty) : 0;
                    $qtyInput.val(qty > 0 ? qty : '');
                }

                if (qty > remainingQty) {
                    qty = remainingQty;
                    $qtyInput.val(remainingQty > 0 ? remainingQty : '');
                }

                if (qty > 0) {
                    $refundInput.val((qty * parseFloat(data.net_rate || 0)).toFixed(2));
                } else if (!$refundInput.val()) {
                    $refundInput.val('');
                }
            }

            function resetItemSelect() {
                $itemSelect.empty().append(new Option('', '', false, false)).trigger('change');
                updateItemSummary(null);
                $refundInput.val('');
                $qtyInput.val('');
            }

            $itemSelect.select2({
                width: '100%',
                placeholder: $itemSelect.data('placeholder') || 'Search invoice item',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: '{{ route('admin.sales.returns.item-options') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            sales_invoice_id: $invoiceSelect.val() || '',
                            sales_return_id: editingReturnId || ''
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });

            $invoiceSelect.on('select2:select', function (event) {
                updateInvoiceSummary(event.params.data || null);
                syncItemSelectState();
                resetItemSelect();
            });

            $invoiceSelect.on('change', function () {
                if (!$(this).val()) {
                    updateInvoiceSummary(null);
                    resetItemSelect();
                }

                syncItemSelectState();
            });

            $itemSelect.on('select2:select', function () {
                syncRefund(false);
            });

            $itemSelect.on('change', function () {
                if (!$(this).val()) {
                    updateItemSummary(null);
                    $refundInput.val('');
                    $qtyInput.val('');
                }
            });

            $qtyInput.on('input', function () {
                syncRefund(true);
            });

            updateInvoiceSummary(currentInvoiceData());
            syncItemSelectState();
            syncRefund(true);
        });
    </script>
@endsection
