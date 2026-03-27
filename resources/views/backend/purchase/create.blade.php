@extends('backend.layouts.main')

@section('title')
    New Purchase Bill
@endsection

@section('main-content')
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
                            <label class="form-label">Supplier <span class="required-field">*</span></label>
                            <select name="supplier_id" class="form-select js-select2-ajax"
                                data-ajax-url="{{ route('admin.purchase.supplier-options') }}"
                                data-placeholder="Search supplier"
                                data-allow-clear="1" required>
                                <option value="">Select Supplier</option>
                                @foreach ($supplier as $supplierItem)
                                    <option value="{{ $supplierItem->id }}">{{ $supplierItem->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice No</label>
                            <input type="text" name="invoice_no" class="form-control" placeholder="Invoice no if available">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Date <span class="required-field">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Small note if needed">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Batch & Expiry Entry</div>
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
                                    <th>Batch No</th>
                                    <th>Expiry</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody data-next-index="1">
                                <tr>
                                    <td class="purchase-row-number">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-select js-select2-ajax"
                                            data-ajax-url="{{ route('admin.purchase.product-options') }}"
                                            data-placeholder="Search product"
                                            data-allow-clear="1" required>
                                            <option value="">Select Product</option>
                                            @foreach ($product as $productItem)
                                                <option value="{{ $productItem->id }}">{{ $productItem->product_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][batch_no]" class="form-control" placeholder="Batch no"></td>
                                    <td><input type="month" name="items[0][expiry_date]" class="form-control" required></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                                    <td><input type="number" name="items[0][purchase_price]" class="form-control price-input" step="0.01" min="0" value="0" required></td>
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
                                <select name="items[__INDEX__][product_id]" class="form-select js-select2-ajax"
                                    data-ajax-url="{{ route('admin.purchase.product-options') }}"
                                    data-placeholder="Search product"
                                    data-allow-clear="1" required>
                                    <option value="">Select Product</option>
                                    @foreach ($product as $productItem)
                                        <option value="{{ $productItem->id }}">{{ $productItem->product_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="items[__INDEX__][batch_no]" class="form-control" placeholder="Batch no"></td>
                            <td><input type="month" name="items[__INDEX__][expiry_date]" class="form-control" required></td>
                            <td><input type="number" name="items[__INDEX__][quantity]" class="form-control qty-input" min="1" value="1" required></td>
                            <td><input type="number" name="items[__INDEX__][purchase_price]" class="form-control price-input" step="0.01" min="0" value="0" required></td>
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
                        <i class="fa fa-save"></i> Save Purchase
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
