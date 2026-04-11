@extends('layouts.main')

@section('title')
    Sales Invoice Create
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Create Sales Invoice</h5>
                <p class="mb-0 text-muted">
                    One invoice can handle retail, wholesale and credit sales from the same form.
                    <span class="ms-2 text-muted">
                        <i class="fa-solid fa-keyboard me-1"></i>Ctrl+Shift+A add row, Ctrl+Enter save
                    </span>
                </p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.sales.store') }}" method="POST" id="salesForm">
            @csrf
            <div class="card custom-card mb-4">
                <div class="card-header">
                    <div class="card-title">Invoice Information</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Reference No</label>
                            <input type="text" class="form-control" value="{{ $reference }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Sale Type</span>
                                @can('settings.manage')
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#salesTypeSelect" data-dropdown-alias="sales_type" data-dropdown-label="Sales Type" data-dropdown-supports-data="0" data-bs-toggle="tooltip" title="Add sales type">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                @endcan
                            </label>
                            <select name="sale_type_id" id="salesTypeSelect" class="form-select js-select2" data-placeholder="Select sale type" data-dropdown-alias="sales_type" required>
                                <option value="">Select sale type</option>
                                @foreach ($saleTypes as $saleType)
                                    <option value="{{ $saleType->id }}">{{ $saleType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Payment Mode</span>
                                @can('settings.manage')
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#salesPaymentMode" data-dropdown-alias="payment_mode" data-dropdown-label="Payment Mode" data-dropdown-supports-data="1" data-bs-toggle="tooltip" title="Add payment mode">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                @endcan
                            </label>
                            <select name="payment_mode_id" id="salesPaymentMode" class="form-select js-select2" data-placeholder="Select mode" data-dropdown-alias="payment_mode" required>
                                <option value="">Select mode</option>
                                @foreach ($paymentModes as $mode)
                                    <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Party</span>
                                @can('party.manage')
                                    <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickCustomerModal" data-quick-target-select="#salesCustomerSelect">
                                        <i class="fa-solid fa-plus"></i> Quick Add
                                    </button>
                                @endcan
                            </label>
                            <select name="customer_id" id="salesCustomerSelect" class="form-select js-select2-ajax" data-ajax-url="{{ route('admin.sales.customer-options') }}" data-placeholder="Search party">
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Short note for billing">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div>
                        <div class="card-title">Billing Items</div>
                        <small class="text-muted">Free goods use product CC rate. Net payable is subtotal minus discount only.</small>
                    </div>
                    <div class="d-flex gap-2">
                        @can('inventory.product')
                            <button type="button" class="btn btn-outline-primary btn-sm js-open-quick-create" data-quick-modal="#quickProductModal">
                                <i class="fa-solid fa-capsules"></i> Quick Add Product
                            </button>
                        @endcan
                        <button type="button" class="btn btn-primary btn-sm" id="addSalesRow">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle purchase-item-table" id="salesItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">S.No</th>
                                    <th>Product</th>
                                    <th style="width: 220px;">Batch</th>
                                    <th style="width: 120px;">Qty</th>
                                    <th style="width: 120px;">Free Qty</th>
                                    <th style="width: 140px;">MRP</th>
                                    <th style="width: 140px;">Unit Price</th>
                                    <th style="width: 120px;">CC Rate %</th>
                                    <th style="width: 120px;">Discount %</th>
                                    <th style="width: 150px;">Free Goods Value</th>
                                    <th style="width: 150px;">Amount</th>
                                    <th style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody data-next-index="1">
                                <tr>
                                    <td class="sales-row-number">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select js-select2-ajax sales-product-select" data-ajax-url="{{ route('admin.sales.product-options') }}" data-product-info-url="{{ route('admin.sales.product-info') }}" data-placeholder="Search product" required>
                                            <option value=""></option>
                                        </select>
                                        <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                                            <small class="text-muted d-block sales-stock-note mb-0">Select product to auto fill price, MRP, CC and stock.</small>
                                            @can('inventory.product')
                                                <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickProductModal" data-quick-target-select="select.sales-product-select">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                    <td>
                                        <select name="items[0][batch_id]" class="form-select sales-batch-select" required>
                                            <option value="">Select batch</option>
                                        </select>
                                    </td>
                                    <td><input type="number" min="1" step="1" name="items[0][quantity]" class="form-control sales-qty-input" value="1" required></td>
                                    <td><input type="number" min="0" step="1" name="items[0][free_qty]" class="form-control sales-free-qty-input" value="0"></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][mrp]" class="form-control sales-mrp-input" value="0" required></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][unit_price]" class="form-control sales-price-input" value="0" required></td>
                                    <td><input type="number" min="0" step="0.01" max="100" name="items[0][cc_rate]" class="form-control sales-cc-rate-input" value="0"></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][discount_percent]" class="form-control sales-discount-input" value="0"></td>
                                    <td><input type="text" class="form-control sales-free-value-input" value="0.00" readonly></td>
                                    <td><input type="text" name="items[0][subtotal]" class="form-control sales-subtotal-input" value="0.00" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger removeSalesRow table-action-btn">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="10" class="text-end fw-semibold">Subtotal</td>
                                    <td>
                                        <input type="text" id="salesSubtotal" class="form-control" value="0.00" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="text-end fw-semibold">Total Discount</td>
                                    <td>
                                        <input type="text" id="salesDiscountTotal" class="form-control" value="0.00" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="text-end fw-semibold">Free Goods Value</td>
                                    <td>
                                        <input type="text" id="salesFreeGoodsTotal" class="form-control" value="0.00" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="10" class="text-end fw-semibold">Net Payable</td>
                                    <td>
                                        <input type="text" id="salesGrandTotal" class="form-control" value="0.00" readonly>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Invoice
                    </button>
                </div>
            </div>
        </form>

        <template id="salesItemTemplate">
            <tr>
                <td class="sales-row-number">__ROW__</td>
                <td>
                    <select name="items[__INDEX__][product_id]" class="form-select js-select2-ajax sales-product-select" data-ajax-url="{{ route('admin.sales.product-options') }}" data-product-info-url="{{ route('admin.sales.product-info') }}" data-placeholder="Search product" required>
                        <option value=""></option>
                    </select>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                        <small class="text-muted d-block sales-stock-note mb-0">Select product to auto fill price, MRP, CC and stock.</small>
                        @can('inventory.product')
                            <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickProductModal" data-quick-target-select="select.sales-product-select">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        @endcan
                    </div>
                </td>
                <td>
                    <select name="items[__INDEX__][batch_id]" class="form-select sales-batch-select" required>
                        <option value="">Select batch</option>
                    </select>
                </td>
                <td><input type="number" min="1" step="1" name="items[__INDEX__][quantity]" class="form-control sales-qty-input" value="1" required></td>
                <td><input type="number" min="0" step="1" name="items[__INDEX__][free_qty]" class="form-control sales-free-qty-input" value="0"></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][mrp]" class="form-control sales-mrp-input" value="0" required></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" class="form-control sales-price-input" value="0" required></td>
                <td><input type="number" min="0" step="0.01" max="100" name="items[__INDEX__][cc_rate]" class="form-control sales-cc-rate-input" value="0"></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][discount_percent]" class="form-control sales-discount-input" value="0"></td>
                <td><input type="text" class="form-control sales-free-value-input" value="0.00" readonly></td>
                <td><input type="text" name="items[__INDEX__][subtotal]" class="form-control sales-subtotal-input" value="0.00" readonly></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger removeSalesRow table-action-btn">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        </template>

        @include('partials.quick-create-modals', [
            'showQuickCustomer' => auth()->user()->can('party.manage'),
            'showQuickPaymentMode' => auth()->user()->can('settings.manage'),
            'showQuickProduct' => auth()->user()->can('inventory.product'),
            'showQuickUnit' => auth()->user()->can('inventory.unit'),
            'companies' => $companies,
            'units' => $units,
            'partyTypes' => $partyTypes,
            'showQuickPartyType' => auth()->user()->can('settings.manage'),
            'formulations' => $formulations,
        ])
    </div>
@endsection
