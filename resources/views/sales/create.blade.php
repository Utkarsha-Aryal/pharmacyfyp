@extends('layouts.main')

@section('title')
    Sales Invoice Create
@endsection

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
                            <label class="form-label">Sale Type</label>
                            <select name="sale_type" class="form-select" required>
                                @foreach ($saleTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                @foreach ($paymentMethods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Party</label>
                            <select name="customer_id" class="form-select js-select2-ajax" data-ajax-url="{{ route('admin.sales.customer-options') }}" data-placeholder="Search party">
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
                        <small class="text-muted">Default tax rate from settings: {{ $taxRate }}%</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="addSalesRow">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle purchase-item-table" id="salesItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">S.No</th>
                                    <th>Product</th>
                                    <th style="width: 120px;">Qty</th>
                                    <th style="width: 140px;">Unit Price</th>
                                    <th style="width: 120px;">Discount %</th>
                                    <th style="width: 120px;">Tax %</th>
                                    <th style="width: 150px;">Subtotal</th>
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
                                        <small class="text-muted d-block sales-stock-note">Select product to auto fill price and stock.</small>
                                    </td>
                                    <td><input type="number" min="1" step="1" name="items[0][quantity]" class="form-control sales-qty-input" value="1" required></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][unit_price]" class="form-control sales-price-input" value="0" required></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][discount_percent]" class="form-control sales-discount-input" value="0"></td>
                                    <td><input type="number" min="0" step="0.01" name="items[0][tax_percent]" class="form-control sales-tax-input" value="{{ $taxRate }}"></td>
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
                                    <td colspan="6" class="text-end fw-semibold">Grand Total</td>
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
                    <small class="text-muted d-block sales-stock-note">Select product to auto fill price and stock.</small>
                </td>
                <td><input type="number" min="1" step="1" name="items[__INDEX__][quantity]" class="form-control sales-qty-input" value="1" required></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" class="form-control sales-price-input" value="0" required></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][discount_percent]" class="form-control sales-discount-input" value="0"></td>
                <td><input type="number" min="0" step="0.01" name="items[__INDEX__][tax_percent]" class="form-control sales-tax-input" value="{{ $taxRate }}"></td>
                <td><input type="text" name="items[__INDEX__][subtotal]" class="form-control sales-subtotal-input" value="0.00" readonly></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger removeSalesRow table-action-btn">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        </template>
    </div>
@endsection
