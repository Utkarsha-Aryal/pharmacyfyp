@extends('layouts.main')

@php
    $isEdit = isset($purchase) && $purchase;
    $purchaseRows = old('items', $itemsRows ?? [[
        'product_id' => '',
        'batch_no' => '',
        'expiry_date' => '',
        'quantity' => 1,
        'free_qty' => 0,
        'mrp' => 0,
        'purchase_price' => 0,
        'cc_rate' => 0,
        'discount_percent' => 0,
    ]]);
@endphp

@section('title')
    {{ $isEdit ? 'Edit Purchase Bill' : 'New Purchase Bill' }}
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $isEdit ? 'Edit Purchase Bill' : 'New Purchase Bill' }}</h5>
            </div>
            <div class="d-flex my-xl-auto right-content">
                <a href="{{ route('admin.purchase') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form id="purchaseForm" action="{{ $isEdit ? route('admin.purchase.update', $purchase) : route('admin.purchase.save') }}" method="POST">
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
                                @foreach ($supplier as $supplierItem)
                                    <option value="{{ $supplierItem->id }}" @selected(old('supplier_id', $purchase->supplier_id ?? '') == $supplierItem->id)>{{ $supplierItem->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice No</label>
                            <input type="text" name="invoice_no" class="form-control" placeholder="Invoice no if available" value="{{ old('invoice_no', $purchase->invoice_no ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Date <span class="required-field">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $purchase->purchase_date ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" id="purchasePaidAmount" class="form-control" step="0.01" min="0" value="{{ old('paid_amount', $purchase->paid_amount ?? 0) }}">
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
                                    <option value="{{ $mode->id }}" @selected(old('payment_mode_id', $purchase->payment_mode_id ?? '') == $mode->id)>{{ $mode->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Small note if needed" value="{{ old('remarks', $purchase->remarks ?? '') }}">
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
                            <tbody data-next-index="{{ count($purchaseRows) }}">
                                @foreach ($purchaseRows as $index => $row)
                                    <tr>
                                        <td class="purchase-row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-select js-select2-ajax purchase-product-select"
                                                data-ajax-url="{{ route('admin.purchase.product-options') }}"
                                                data-product-info-url="{{ route('admin.purchase.product-info') }}"
                                                data-placeholder="Search product"
                                                data-allow-clear="1" required>
                                                <option value="">Select Product</option>
                                                @foreach ($product as $productItem)
                                                    <option value="{{ $productItem->id }}" @selected(($row['product_id'] ?? '') == $productItem->id)>{{ $productItem->product_name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex justify-content-end align-items-center gap-2 mt-1">
                                                @can('inventory.product')
                                                    <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickProductModal" data-quick-target-select="select.purchase-product-select">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                        <td><input type="text" name="items[{{ $index }}][batch_no]" class="form-control" placeholder="Batch no" value="{{ $row['batch_no'] ?? '' }}"></td>
                                        <td><input type="date" name="items[{{ $index }}][expiry_date]" class="form-control" value="{{ $row['expiry_date'] ?? '' }}" required></td>
                                        <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control qty-input" min="1" value="{{ $row['quantity'] ?? 1 }}" required></td>
                                        <td><input type="number" name="items[{{ $index }}][free_qty]" class="form-control free-qty-input" min="0" value="{{ $row['free_qty'] ?? 0 }}"></td>
                                        <td><input type="number" name="items[{{ $index }}][mrp]" class="form-control mrp-input" step="0.01" min="0" value="{{ $row['mrp'] ?? 0 }}" required></td>
                                        <td><input type="number" name="items[{{ $index }}][purchase_price]" class="form-control price-input" step="0.01" min="0" value="{{ $row['purchase_price'] ?? 0 }}" required></td>
                                        <td><input type="number" name="items[{{ $index }}][cc_rate]" class="form-control cc-rate-input" step="0.01" min="0" max="100" value="{{ $row['cc_rate'] ?? 0 }}"></td>
                                        <td><input type="number" name="items[{{ $index }}][discount_percent]" class="form-control discount-input" step="0.01" min="0" max="100" value="{{ $row['discount_percent'] ?? 0 }}"></td>
                                        <td><input type="text" class="form-control free-goods-input" value="0.00" readonly></td>
                                        <td><input type="text" class="form-control subtotal-input" value="0.00" readonly></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger removePurchaseRow">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
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
                                <div class="d-flex justify-content-end align-items-center gap-2 mt-1">
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
                        <i class="fa fa-save"></i> {{ $isEdit ? 'Update Purchase' : 'Save Purchase' }}
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
