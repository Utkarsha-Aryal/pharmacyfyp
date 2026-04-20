@extends('layouts.main')

@section('title')
    New Purchase Bill
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    @php
        $ocrDraft = session('ocr_draft', []);
        $ocrSummary = $ocrDraft['summary'] ?? [];
    @endphp

    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">New Purchase Bill</h5>
                <p class="mb-0 text-muted">Save received supplier bill and create stock batch in the same step.</p>
            </div>
            <div class="d-flex my-xl-auto right-content">
                <a href="{{ route('admin.purchase') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if (!empty($ocrDraft) || session('ocr_text'))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <strong>OCR draft loaded.</strong>
                @if (!empty($ocrSummary['supplier_name']) || !empty($ocrSummary['invoice_no']))
                    I found {{ $ocrSummary['supplier_name'] ?? 'a supplier' }} and invoice {{ $ocrSummary['invoice_no'] ?? 'number' }}.
                    @if (!empty($ocrSummary['matches']))
                        There are {{ count($ocrSummary['matches']) }} matching bill(s) already in the system.
                        @if (!empty($ocrDraft['selected_purchase_id']))
                            Selected bill id: {{ $ocrDraft['selected_purchase_id'] }}.
                        @endif
                    @else
                        No matching bill was found, so I can create a fresh draft.
                    @endif
                @else
                    The scan did not clearly identify the supplier or invoice number, so I loaded the raw text for manual review.
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form id="purchaseForm" action="{{ route('admin.purchase.save') }}" method="POST">
            @csrf
            <input type="hidden" name="reference_id" value="{{ $reference->id }}">

            <div class="card custom-card mb-4">
                <div class="card-header">
                    <div class="card-title">Purchase Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Reference No</label>
                            <input type="text" class="form-control" value="{{ $reference->reference_no }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">Supplier <span class="d-inline-flex align-items-center gap-2">
                                    <span class="required-field">*</span>
                                    @can('purchase.supplier')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickSupplierModal" data-quick-target-select="#purchaseSupplierSelect">
                                            <i class="fa-solid fa-plus"></i> Quick Add
                                        </button>
                                    @endcan
                                </span></label>
                            <select name="supplier_id" id="purchaseSupplierSelect" class="form-select js-select2-ajax"
                                data-ajax-url="{{ route('admin.purchase.supplier-options') }}"
                                data-placeholder="Search supplier"
                                data-allow-clear="1" required>
                                <option value="">Select Supplier</option>
                                @if (!empty($ocrSummary['supplier_id']) && !empty($ocrSummary['supplier_name']))
                                    <option value="{{ $ocrSummary['supplier_id'] }}" selected>{{ $ocrSummary['supplier_name'] }}</option>
                                @endif
                                @foreach ($supplier as $supplierItem)
                                    <option value="{{ $supplierItem->id }}">{{ $supplierItem->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice No</label>
                            <input type="text" name="invoice_no" class="form-control" placeholder="Invoice no if available" value="{{ old('invoice_no', $ocrSummary['invoice_no'] ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Date <span class="required-field">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $ocrSummary['invoice_date'] ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" id="purchasePaidAmount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Payment Mode</span>
                                @can('settings.manage')
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#purchasePaymentMode" data-dropdown-alias="payment_mode" data-dropdown-label="Payment Mode" data-dropdown-supports-data="1" data-bs-toggle="tooltip" title="Add payment mode">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                @endcan
                            </label>
                            <select name="payment_mode_id" id="purchasePaymentMode" class="form-select js-select2" data-placeholder="Select mode" data-dropdown-alias="payment_mode">
                                <option value="">Select mode</option>
                                @foreach ($paymentModes as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1" id="purchasePaymentModeHelp">Optional until money is paid to the supplier.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Small note if needed" value="{{ old('remarks', session('ocr_text')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Batch & Expiry Entry</div>
                    <div class="d-flex gap-2">
                        @can('inventory.product')
                            <button type="button" class="btn btn-outline-primary btn-sm js-open-quick-create" data-quick-modal="#quickProductModal">
                                <i class="fa-solid fa-capsules"></i> Quick Add Product
                            </button>
                        @endcan
                        <button type="button" class="btn btn-primary btn-sm" id="addPurchaseRow">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered purchase-item-table" id="purchaseItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">S.No</th>
                                    <th>Product</th>
                                    <th>Batch No</th>
                                    <th>Expiry Date</th>
                                    <th>Qty</th>
                                    <th>Free Qty</th>
                                    <th>MRP</th>
                                    <th>Rate</th>
                                    <th>CC Rate%</th>
                                    <th>Disc%</th>
                                    <th>Free Goods Value</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody data-next-index="1">
                                <tr>
                                    <td class="purchase-row-number">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select js-select2-ajax purchase-product-select"
                                            data-ajax-url="{{ route('admin.purchase.product-options') }}"
                                            data-product-info-url="{{ route('admin.purchase.product-info') }}"
                                            data-placeholder="Search product"
                                            data-allow-clear="1" required>
                                            <option value="">Select Product</option>
                                            @foreach ($product as $productItem)
                                                <option value="{{ $productItem->id }}">{{ $productItem->product_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                                            <small class="text-muted d-block purchase-stock-note mb-0">Select product to auto fill MRP, CC rate and latest purchase rate.</small>
                                            @can('inventory.product')
                                                <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickProductModal" data-quick-target-select="select.purchase-product-select">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td><input type="text" name="items[0][batch_no]" class="form-control" placeholder="Batch no"></td>
                                    <td><input type="date" name="items[0][expiry_date]" class="form-control" required></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                                    <td><input type="number" name="items[0][free_qty]" class="form-control free-qty-input" min="0" value="0"></td>
                                    <td><input type="number" name="items[0][mrp]" class="form-control mrp-input" step="0.01" min="0" value="0" required></td>
                                    <td><input type="number" name="items[0][purchase_price]" class="form-control price-input" step="0.01" min="0" value="0" required></td>
                                    <td><input type="number" name="items[0][cc_rate]" class="form-control cc-rate-input" step="0.01" min="0" max="100" value="0"></td>
                                    <td><input type="number" name="items[0][discount_percent]" class="form-control discount-input" step="0.01" min="0" max="100" value="0"></td>
                                    <td><input type="text" class="form-control free-goods-input" value="0.00" readonly></td>
                                    <td><input type="text" class="form-control subtotal-input" value="0.00" readonly></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger removePurchaseRow">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template id="purchaseItemTemplate">
                        <tr>
                            <td class="purchase-row-number">__ROW__</td>
                            <td>
                                <select name="items[__INDEX__][product_id]" class="form-select js-select2-ajax purchase-product-select"
                                    data-ajax-url="{{ route('admin.purchase.product-options') }}"
                                    data-product-info-url="{{ route('admin.purchase.product-info') }}"
                                    data-placeholder="Search product"
                                    data-allow-clear="1" required>
                                    <option value="">Select Product</option>
                                @foreach ($product as $productItem)
                                    <option value="{{ $productItem->id }}">{{ $productItem->product_name }}</option>
                                @endforeach
                            </select>
                                <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                                    <small class="text-muted d-block purchase-stock-note mb-0">Select product to auto fill MRP, CC rate and latest purchase rate.</small>
                                    @can('inventory.product')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickProductModal" data-quick-target-select="select.purchase-product-select">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                            <td><input type="text" name="items[__INDEX__][batch_no]" class="form-control" placeholder="Batch no"></td>
                            <td><input type="date" name="items[__INDEX__][expiry_date]" class="form-control" required></td>
                            <td><input type="number" name="items[__INDEX__][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                            <td><input type="number" name="items[__INDEX__][free_qty]" class="form-control free-qty-input" min="0" value="0"></td>
                            <td><input type="number" name="items[__INDEX__][mrp]" class="form-control mrp-input" step="0.01" min="0" value="0" required></td>
                            <td><input type="number" name="items[__INDEX__][purchase_price]" class="form-control price-input" step="0.01" min="0" value="0" required></td>
                            <td><input type="number" name="items[__INDEX__][cc_rate]" class="form-control cc-rate-input" step="0.01" min="0" max="100" value="0"></td>
                            <td><input type="number" name="items[__INDEX__][discount_percent]" class="form-control discount-input" step="0.01" min="0" max="100" value="0"></td>
                            <td><input type="text" class="form-control free-goods-input" value="0.00" readonly></td>
                            <td><input type="text" class="form-control subtotal-input" value="0.00" readonly></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger removePurchaseRow">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <div class="purchase-total-box mt-4">
                        <div class="purchase-total-row">
                            <span>Subtotal</span>
                            <input type="text" id="purchaseSubtotal" class="form-control" value="0.00" readonly>
                        </div>
                        <div class="purchase-total-row">
                            <span>Total Discount</span>
                            <input type="text" id="purchaseDiscountTotal" class="form-control" value="0.00" readonly>
                        </div>
                        <div class="purchase-total-row">
                            <span>Free Goods Total</span>
                            <input type="text" id="purchaseFreeGoodsTotal" class="form-control" value="0.00" readonly>
                        </div>
                        <div class="purchase-total-row">
                            <span>Net Payable</span>
                            <input type="text" id="grandTotal" class="form-control" value="0.00" readonly>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Purchase
                    </button>
                </div>
            </div>
        </form>

        @include('partials.quick-create-modals', [
            'showQuickSupplier' => auth()->user()->can('purchase.supplier'),
            'showQuickSupplierType' => auth()->user()->can('settings.manage'),
            'showQuickPaymentMode' => auth()->user()->can('settings.manage'),
            'showQuickProduct' => auth()->user()->can('inventory.product'),
            'showQuickUnit' => auth()->user()->can('inventory.unit'),
            'companies' => $companies,
            'units' => $units,
            'formulations' => $formulations,
            'supplierTypes' => $supplierTypes,
        ])
    </div>
@endsection
