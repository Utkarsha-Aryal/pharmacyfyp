@php
    $categories = $categories ?? collect();
    $units = $units ?? collect();
    $showQuickCustomer = $showQuickCustomer ?? false;
    $showQuickSupplier = $showQuickSupplier ?? false;
    $showQuickPaymentMode = $showQuickPaymentMode ?? false;
    $showQuickProduct = $showQuickProduct ?? false;
    $showQuickUnit = $showQuickUnit ?? false;
@endphp

@if ($showQuickCustomer)
    <div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.customers.save') }}" method="POST" class="js-quick-create-form" data-result-kind="customer">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Party Add</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Party Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="party_type" class="form-select" required>
                                    <option value="customer">Customer</option>
                                    <option value="institution">Institution</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Party</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($showQuickSupplier)
    <div class="modal fade" id="quickSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.supplier.save') }}" method="POST" class="js-quick-create-form" data-result-kind="supplier">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Supplier Add</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" name="supplier_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="credit">Credit</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($showQuickPaymentMode)
    <div class="modal fade" id="quickPaymentModeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST"
                    id="quickPaymentModeForm"
                    class="js-quick-payment-mode-form"
                    data-list-url="{{ route('admin.payment-modes.index') }}"
                    data-store-url="{{ route('admin.payment-modes.store') }}"
                    data-update-url-template="{{ url('admin/payment-modes/__ID__/update') }}"
                    data-delete-url-template="{{ url('admin/payment-modes/__ID__/delete') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickPaymentModeModalTitle">Manage Payment Modes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="payment_mode_id" id="quick_payment_mode_id">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label">Mode Name</label>
                                <input type="text" name="name" id="quick_payment_mode_name" class="form-control" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Type</label>
                                <select name="type" id="quick_payment_mode_type" class="form-select" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="digital">Digital</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="quickPaymentModeResetBtn">Reset</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="quickPaymentModeSubmitBtn"><i class="fa-solid fa-save"></i> Save Mode</button>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-12">
                                <div class="small text-muted mb-2">Quick manage is handy here because sales, purchase, and payments all use the same mode list.</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="quickPaymentModeTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">S.No</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th style="width: 150px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Loading payment modes...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($showQuickProduct)
    <div class="modal fade" id="quickProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.product.quick-save') }}" method="POST" class="js-quick-create-form" data-result-kind="product">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Product Add</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="product_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Generic Name</label>
                                <input type="text" name="generic_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select js-select2" data-placeholder="Select category" required>
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Sale Unit</span>
                                    @if ($showQuickUnit)
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickUnitModal" data-quick-target-select="[name='unit_sale_id']">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="unit_sale_id" class="form-select js-select2" data-placeholder="Select unit" required>
                                    <option value="">Select sale unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Purchase Unit</span>
                                    @if ($showQuickUnit)
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickUnitModal" data-quick-target-select="[name='unit_purchase_id']">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="unit_purchase_id" class="form-select js-select2" data-placeholder="Select unit" required>
                                    <option value="">Select purchase unit</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">MRP</label>
                                <input type="number" step="0.01" min="0" name="mrp" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CC Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="cc_rate" class="form-control" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Purchase Price</label>
                                <input type="number" step="0.01" min="0" name="purchase_price" class="form-control" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" min="0" name="reorder_level" class="form-control" value="10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Formulation</label>
                                <select name="formulation" class="form-select">
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="syrup">Syrup</option>
                                    <option value="injection">Injection</option>
                                    <option value="cream">Cream</option>
                                    <option value="drops">Drops</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Small quick note for this product"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($showQuickUnit)
    <div class="modal fade" id="quickUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.unit.save') }}" method="POST" class="js-quick-create-form" data-result-kind="unit">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Unit Add</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Unit Name</label>
                                <input type="text" name="unit_name" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <input type="hidden" name="status" value="Y">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
