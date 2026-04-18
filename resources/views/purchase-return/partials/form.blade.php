@php
    $purchaseReturn = $purchaseReturn ?? null;
    $itemsRows = $itemsRows ?? [];
    $selectedSupplierId = old('supplier_id', $purchaseReturn?->supplier_id ?? '');
    $selectedReturnMode = old('return_mode', $purchaseReturn ? ($purchaseReturn->purchase_id ? 'bill' : 'product') : 'bill');
    $selectedReturnDate = old('return_date', $purchaseReturn?->return_date ?? now()->toDateString());
    $selectedNotes = old('notes', $purchaseReturn?->notes ?? '');
    $selectedPurchaseId = old('purchase_id', $purchaseReturn?->purchase_id ?? '');
@endphp

<div class="admin-page-wrap">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">{{ $pageHeading }}</h5>
            <p class="mb-0 text-muted">{{ $pageDescription }}</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ $formAction }}" method="POST" id="purchaseReturnForm">
        @csrf

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Return Information</div>
            </div>
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
                                <option value="{{ $supplier->id }}" @selected((int) $selectedSupplierId === (int) $supplier->id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Return Mode</label>
                        <div class="d-flex flex-wrap gap-3 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_mode" id="purchaseReturnModeBill" value="bill" @checked($selectedReturnMode === 'bill')>
                                <label class="form-check-label" for="purchaseReturnModeBill">By Purchase Bill</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="return_mode" id="purchaseReturnModeProduct" value="product" @checked($selectedReturnMode === 'product')>
                                <label class="form-check-label" for="purchaseReturnModeProduct">By Product &amp; Batch</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ $selectedReturnDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Short note" value="{{ $selectedNotes }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purchase Bill</label>
                        <select name="purchase_id" id="purchaseReturnPurchase" class="form-select js-select2" data-placeholder="Select purchase">
                            @if (!empty($selectedPurchaseOption))
                                <option value="{{ $selectedPurchaseOption['id'] }}" @selected((int) $selectedPurchaseId === (int) $selectedPurchaseOption['id'])>{{ $selectedPurchaseOption['text'] }}</option>
                            @else
                                <option value="">Select purchase</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-light border mb-0 small text-muted h-100 d-flex align-items-center" id="purchaseReturnModeHelp">
                            Purchase bill mode loads all returnable lines from one bill. Product mode lets you add rows one by one like invoice entry when the bill is not known.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div>
                    <div class="card-title">Return Items</div>
                    <small class="text-muted">Bill mode loads purchase lines. Product mode adds rows the same way as invoice entry.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="purchaseReturnLoadBillItems">
                        <i class="fa-solid fa-file-invoice"></i> Load Bill Items
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="purchaseReturnAddManualItem">
                        <i class="fa-solid fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle purchase-item-table" id="purchaseReturnItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th>Product</th>
                                <th style="width: 220px;">Batch</th>
                                <th style="width: 110px;">Original</th>
                                <th style="width: 120px;">Returned</th>
                                <th style="width: 120px;">Available</th>
                                <th style="width: 120px;">Return Qty</th>
                                <th style="width: 130px;">Rate</th>
                                <th style="width: 120px;">Discount %</th>
                                <th style="width: 130px;">Discount Amt</th>
                                <th style="width: 130px;">Net Rate</th>
                                <th style="width: 140px;">Amount</th>
                                <th style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody data-next-index="{{ count($itemsRows) }}">
                            @forelse ($itemsRows as $index => $row)
                                @php
                                    $isBillRow = $purchaseReturn?->purchase_id && !empty($row['purchase_item_id']);
                                    $rowMax = (float) ($row['max_returnable'] ?? 0);
                                @endphp
                                <tr data-row-mode="{{ $isBillRow ? 'bill' : 'manual' }}" data-pricing-mode="percent" data-base-max-returnable="{{ $rowMax }}">
                                    <td class="purchase-return-row-number">{{ $index + 1 }}</td>
                                    <td>
                                        @if ($isBillRow)
                                            <div class="fw-semibold">{{ $row['product_name'] }}</div>
                                            <small class="text-muted d-block">Loaded from the selected purchase bill.</small>
                                            <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $row['purchase_item_id'] }}">
                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $row['product_id'] }}">
                                        @else
                                            <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="">
                                            <select name="items[{{ $index }}][product_id]" class="form-select js-select2-ajax purchase-return-product-select" data-ajax-url="{{ route('admin.purchase.product-options') }}" data-placeholder="Search product" data-allow-clear="1">
                                                <option value="{{ $row['product_id'] }}">{{ $row['product_name'] }}</option>
                                            </select>
                                            <small class="text-muted d-block mt-1 purchase-return-product-note">Change product to refresh supplier batches for this row.</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <select name="items[{{ $index }}][batch_id]" class="form-select form-select-sm purchase-return-batch-select" @disabled(empty($row['batch_options']))>
                                                <option value="">{{ $isBillRow ? 'Select batch' : 'Choose batch' }}</option>
                                                @foreach ($row['batch_options'] as $batchOption)
                                                    <option
                                                        value="{{ $batchOption['id'] }}"
                                                        data-badge-class="{{ $batchOption['badge_class'] }}"
                                                        data-badge-label="{{ $batchOption['badge_label'] }}"
                                                        data-quantity-available="{{ $batchOption['quantity_available'] }}"
                                                        data-quantity-received="{{ $batchOption['quantity_received'] ?? $row['original_qty'] }}"
                                                        data-purchase-price="{{ $batchOption['purchase_price'] ?? $row['rate'] }}"
                                                        @selected((int) ($row['selected_batch_id'] ?? 0) === (int) $batchOption['id'])
                                                    >
                                                        {{ $batchOption['text'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="badge purchase-return-batch-badge {{ $row['batch_badge_class'] }}">{{ $row['batch_badge_label'] }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border purchase-return-original-label">{{ number_format((float) ($row['original_qty'] ?? 0), 0) }}</span></td>
                                    <td><span class="badge bg-secondary purchase-return-returned-label">{{ number_format((float) ($row['already_returned'] ?? 0), 0) }}</span></td>
                                    <td><span class="badge bg-info text-dark purchase-return-max-label">{{ number_format($rowMax, 0) }}</span></td>
                                    <td><input type="number" name="items[{{ $index }}][return_qty]" class="form-control purchase-return-qty-input" min="0" max="{{ $rowMax }}" value="{{ $row['return_qty'] ?? 0 }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][rate]" class="form-control purchase-return-rate-input" min="0" step="0.01" value="{{ $row['rate'] ?? 0 }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][discount_percent]" class="form-control purchase-return-discount-input" min="0" max="100" step="0.01" value="{{ $row['discount_percent'] ?? 0 }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][discount_amount]" class="form-control purchase-return-discount-amount-input" min="0" step="0.01" value="{{ $row['discount_amount'] ?? 0 }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][net_rate]" class="form-control purchase-return-net-rate-input" min="0" step="0.01" value="{{ $row['net_rate'] ?? 0 }}"></td>
                                    <td><input type="text" name="items[{{ $index }}][return_amount]" class="form-control purchase-return-amount-input" value="{{ $row['return_amount'] ?? 0 }}" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger removePurchaseReturnRow table-action-btn">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="purchase-return-empty-row">
                                    <td colspan="13" class="text-center text-muted">Select a purchase bill or switch to product mode to start adding return rows.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="11" class="text-end fw-semibold">Total Return Qty</th>
                                <th><input type="text" id="purchaseReturnQtyTotal" class="form-control" value="0" readonly></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="11" class="text-end fw-semibold">Gross Return</th>
                                <th><input type="text" id="purchaseReturnGrossTotal" class="form-control" value="0.00" readonly></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="11" class="text-end fw-semibold">Total Discount</th>
                                <th><input type="text" id="purchaseReturnDiscountTotal" class="form-control" value="0.00" readonly></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="11" class="text-end fw-semibold">Net Return</th>
                                <th><input type="text" id="purchaseReturnAmountTotal" class="form-control" value="0.00" readonly></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <template id="purchaseReturnManualRowTemplate">
                    <tr data-row-mode="manual" data-pricing-mode="percent" data-base-max-returnable="0">
                        <td class="purchase-return-row-number">__ROW__</td>
                        <td>
                            <input type="hidden" name="items[__INDEX__][purchase_item_id]" value="">
                            <select name="items[__INDEX__][product_id]" class="form-select js-select2-ajax purchase-return-product-select" data-ajax-url="{{ route('admin.purchase.product-options') }}" data-placeholder="Search product" data-allow-clear="1">
                                <option value=""></option>
                            </select>
                            <small class="text-muted d-block mt-1 purchase-return-product-note">Choose product to load supplier batches for this row.</small>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <select name="items[__INDEX__][batch_id]" class="form-select form-select-sm purchase-return-batch-select" disabled>
                                    <option value="">Choose product first</option>
                                </select>
                                <span class="badge purchase-return-batch-badge bg-light text-dark border">Waiting for product</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border purchase-return-original-label">0</span></td>
                        <td><span class="badge bg-secondary purchase-return-returned-label">0</span></td>
                        <td><span class="badge bg-info text-dark purchase-return-max-label">0</span></td>
                        <td><input type="number" name="items[__INDEX__][return_qty]" class="form-control purchase-return-qty-input" min="0" max="0" value="0"></td>
                        <td><input type="number" name="items[__INDEX__][rate]" class="form-control purchase-return-rate-input" min="0" step="0.01" value="0"></td>
                        <td><input type="number" name="items[__INDEX__][discount_percent]" class="form-control purchase-return-discount-input" min="0" max="100" step="0.01" value="0"></td>
                        <td><input type="number" name="items[__INDEX__][discount_amount]" class="form-control purchase-return-discount-amount-input" min="0" step="0.01" value="0"></td>
                        <td><input type="number" name="items[__INDEX__][net_rate]" class="form-control purchase-return-net-rate-input" min="0" step="0.01" value="0"></td>
                        <td><input type="text" name="items[__INDEX__][return_amount]" class="form-control purchase-return-amount-input" value="0.00" readonly></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger removePurchaseReturnRow table-action-btn">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                    @if ($purchaseReturn)
                        <button type="submit" form="purchaseReturnDeleteForm" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ $submitLabel }}
                </button>
            </div>
        </div>
    </form>

    @if ($purchaseReturn)
        <form id="purchaseReturnDeleteForm" action="{{ route('admin.purchase-returns.delete', $purchaseReturn) }}" method="POST" class="d-none js-confirm-submit" data-confirm-title="Delete purchase return?" data-confirm-text="This will restore the stock back to inventory." data-confirm-button="Yes, delete it">
            @csrf
        </form>
    @endif

    @include('partials.quick-create-modals', [
        'showQuickSupplier' => auth()->user()->can('purchase.supplier'),
        'showQuickSupplierType' => auth()->user()->can('settings.manage'),
        'supplierTypes' => $supplierTypes ?? collect(),
    ])
</div>
