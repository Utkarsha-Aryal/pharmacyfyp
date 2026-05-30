@extends('layouts.main')

@php
    $isEdit = isset($order) && $order;
    $orderRows = old('items', $orderRows ?? [[
        'product_id' => '',
        'quantity_ordered' => 1,
        'unit_price' => 0,
    ]]);
@endphp

@section('title')
    {{ $isEdit ? 'Edit Purchase Order' : 'Create Purchase Order' }}
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $isEdit ? 'Edit Purchase Order' : 'Create Purchase Order' }}</h5>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form id="purchaseForm" action="{{ $isEdit ? route('admin.purchase-orders.update', $order) : route('admin.purchase-orders.store') }}" method="POST">
            @csrf
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <div class="card-title">Order Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Reference No</label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference', $reference) }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Supplier <span class="required-field">*</span></span>
                                <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add supplier" data-quick-modal="#quickSupplierModal" data-quick-target-select="#purchaseOrderSupplierSelect">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </label>
                            <select name="supplier_id" id="purchaseOrderSupplierSelect" class="form-select js-select2-ajax" data-ajax-url="{{ route('admin.purchase-orders.supplier-options') }}" data-placeholder="Search supplier" data-allow-clear="1" required>
                                <option value="">Select Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $order->supplier_id ?? '') == $supplier->id)>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Date <span class="required-field">*</span></label>
                            <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $order->order_date ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expected Delivery</label>
                            <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', $order->expected_delivery_date ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" class="form-control" min="0" step="0.01" value="{{ old('paid_amount', $order->paid_amount ?? 0) }}">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Small note if needed" value="{{ old('notes', $order->notes ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Order Items</div>
                    <button type="button" class="btn btn-primary btn-sm" id="addPurchaseRow">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered purchase-item-table" id="purchaseItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">S.No</th>
                                    <th>Product</th>
                                    <th>Qty Ordered</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody data-next-index="{{ count($orderRows) }}">
                                @foreach ($orderRows as $index => $row)
                                    <tr>
                                        <td class="purchase-row-number">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-start">
                                                <div class="flex-grow-1">
                                                    <select name="items[{{ $index }}][product_id]" class="form-select js-select2-ajax" data-ajax-url="{{ route('admin.purchase-orders.product-options') }}" data-placeholder="Search product" data-allow-clear="1" required>
                                                        <option value="">Select Product</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}" @selected(($row['product_id'] ?? '') == $product->id)>{{ $product->display_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create mt-1" data-bs-toggle="tooltip" title="Quick add product" data-quick-modal="#quickProductModal" data-quick-target-select="select[name='items[{{ $index }}][product_id]']">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td><input type="number" name="items[{{ $index }}][quantity_ordered]" class="form-control qty-input" min="1" value="{{ $row['quantity_ordered'] ?? 1 }}" required></td>
                                        <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control price-input" step="0.01" min="0" value="{{ $row['unit_price'] ?? 0 }}" required></td>
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
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1">
                                        <select name="items[__INDEX__][product_id]" class="form-select js-select2-ajax" data-ajax-url="{{ route('admin.purchase-orders.product-options') }}" data-placeholder="Search product" data-allow-clear="1" required>
                                            <option value="">Select Product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create mt-1" data-bs-toggle="tooltip" title="Quick add product" data-quick-modal="#quickProductModal" data-quick-target-select="select[name='items[__INDEX__][product_id]']">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                            <td><input type="number" name="items[__INDEX__][quantity_ordered]" class="form-control qty-input" min="1" value="1" required></td>
                            <td><input type="number" name="items[__INDEX__][unit_price]" class="form-control price-input" step="0.01" min="0" value="0" required></td>
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
                            <span>Grand Total</span>
                            <input type="text" id="grandTotal" class="form-control" value="0.00" readonly>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> {{ $isEdit ? 'Update Order' : 'Save Order' }}
                    </button>
                </div>
            </div>
        </form>

        @include('partials.quick-create-modals', [
            'showQuickSupplier' => auth()->user()->can('purchase.supplier'),
            'showQuickSupplierType' => auth()->user()->can('settings.manage'),
            'showQuickProduct' => auth()->user()->can('inventory.product'),
            'showQuickPaymentMode' => auth()->user()->can('settings.manage'),
            'showQuickUnit' => auth()->user()->can('inventory.unit'),
            'companies' => $companies,
            'units' => $units,
            'formulations' => $formulations,
            'supplierTypes' => $supplierTypes,
        ])
    </div>
@endsection
