@php
    $categories = $categories ?? collect();
    $units = $units ?? collect();
    $saleUnits = $saleUnits ?? $units->whereIn('type', ['sales', 'both'])->values();
    $purchaseUnits = $purchaseUnits ?? $units->whereIn('type', ['purchase', 'both'])->values();
    $formulations = $formulations ?? collect();
    $productStatuses = $productStatuses ?? \App\Models\DropdownOption::query()->forAlias('product_status')->active()->orderBy('name')->get();
    $expenseCategories = $expenseCategories ?? collect();
    $partyTypes = $partyTypes ?? collect([
        (object) ['name' => 'Customer', 'code' => 'customer'],
        (object) ['name' => 'Institution', 'code' => 'institution'],
    ]);
    $supplierTypes = $supplierTypes ?? collect([
        (object) ['name' => 'Credit', 'code' => 'credit'],
        (object) ['name' => 'Debit', 'code' => 'debit'],
    ]);
    $showQuickCustomer = $showQuickCustomer ?? false;
    $showQuickSupplier = $showQuickSupplier ?? false;
    $showQuickPaymentMode = $showQuickPaymentMode ?? false;
    $showQuickExpenseCategory = $showQuickExpenseCategory ?? false;
    $showQuickDropdownOption = $showQuickDropdownOption ?? ($showQuickPaymentMode || $showQuickExpenseCategory);
    $showQuickPartyType = $showQuickPartyType ?? false;
    $showQuickSupplierType = $showQuickSupplierType ?? false;
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
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Party Type</span>
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add party type" data-quick-modal="#quickPartyTypeModal" data-quick-target-select="#quickCustomerPartyType">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </label>
                                <select name="party_type" id="quickCustomerPartyType" class="form-select js-party-type-select" required>
                                    @foreach ($partyTypes as $partyType)
                                        <option value="{{ $partyType->code }}">{{ $partyType->name }}</option>
                                    @endforeach
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

@if ($showQuickSupplierType)
    <div class="modal fade" id="quickSupplierTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST"
                    id="quickSupplierTypeForm"
                    class="js-quick-supplier-type-form"
                    data-list-url="{{ route('admin.supplier-types.index') }}"
                    data-store-url="{{ route('admin.supplier-types.store') }}"
                    data-update-url-template="{{ url('admin/supplier-types/__ID__/update') }}"
                    data-delete-url-template="{{ url('admin/supplier-types/__ID__/delete') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickSupplierTypeModalTitle">Manage Supplier Types</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="supplier_type_id" id="quick_supplier_type_id">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Supplier Type Name</label>
                                <input type="text" name="name" id="quick_supplier_type_name" class="form-control" placeholder="Credit" required>
                                <small class="text-muted">The code is created from this name automatically.</small>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="quickSupplierTypeResetBtn">Reset</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="quickSupplierTypeSubmitBtn"><i class="fa-solid fa-save"></i> Save Supplier Type</button>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-12">
                                <div class="small text-muted mb-2">Supplier types keep supplier labels reusable across the app.</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="quickSupplierTypeTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">S.No</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Status</th>
                                                <th style="width: 150px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Loading supplier types...</td>
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
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Type</span>
                                    <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add supplier type" data-quick-modal="#quickSupplierTypeModal" data-quick-target-select="[name='type']">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </label>
                                <select name="type" class="form-select js-supplier-type-select">
                                    @foreach ($supplierTypes as $supplierType)
                                        <option value="{{ $supplierType->code }}">{{ $supplierType->name }}</option>
                                    @endforeach
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

@if ($showQuickPartyType)
    <div class="modal fade" id="quickPartyTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST"
                    id="quickPartyTypeForm"
                    class="js-quick-party-type-form"
                    data-list-url="{{ route('admin.party-types.index') }}"
                    data-store-url="{{ route('admin.party-types.store') }}"
                    data-update-url-template="{{ url('admin/party-types/__ID__/update') }}"
                    data-delete-url-template="{{ url('admin/party-types/__ID__/delete') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickPartyTypeModalTitle">Manage Party Types</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="party_type_id" id="quick_party_type_id">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Party Type Name</label>
                                <input type="text" name="name" id="quick_party_type_name" class="form-control" placeholder="Institution" required>
                                <small class="text-muted">The code is created from this name automatically.</small>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="quickPartyTypeResetBtn">Reset</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="quickPartyTypeSubmitBtn"><i class="fa-solid fa-save"></i> Save Party Type</button>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-12">
                                <div class="small text-muted mb-2">Party types keep the customer and institution labels reusable across the app.</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="quickPartyTypeTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">S.No</th>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Status</th>
                                                <th style="width: 150px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Loading party types...</td>
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

@if ($showQuickDropdownOption)
    <div class="modal fade" id="quickDropdownOptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST"
                    id="quickDropdownOptionForm"
                    class="js-quick-dropdown-option-form"
                    data-list-url="{{ route('admin.settings.options.index') }}"
                    data-store-url="{{ route('admin.settings.options.store') }}"
                    data-update-url-template="{{ url('admin/settings/options/__ID__') }}"
                    data-delete-url-template="{{ url('admin/settings/options/__ID__') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickDropdownOptionModalTitle">Manage Options</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="alias" id="quick_dropdown_option_alias">
                        <input type="hidden" id="quick_dropdown_option_id">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" id="quickDropdownOptionNameLabel">Option Name</label>
                                <input type="text" name="name" id="quick_dropdown_option_name" class="form-control" required>
                            </div>
                            <div class="col-md-12" id="quickDropdownOptionDataWrap">
                                <label class="form-label" id="quickDropdownOptionDataLabel">Data</label>
                                <input type="text" name="data" id="quick_dropdown_option_data" class="form-control" placeholder="Optional metadata">
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="quick_dropdown_option_status" checked>
                                    <label class="form-check-label" for="quick_dropdown_option_status">Active</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="quickDropdownOptionResetBtn">Reset</button>
                                <button type="submit" class="btn btn-primary btn-sm" id="quickDropdownOptionSubmitBtn"><i class="fa-solid fa-save"></i> Save Option</button>
                            </div>
                            <div class="col-12">
                                <hr class="my-1">
                            </div>
                            <div class="col-12">
                                <div class="small text-muted mb-2" id="quickDropdownOptionHelpText">Use this quick modal when an option is missing during entry.</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="quickDropdownOptionTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">S.No</th>
                                                <th>Name</th>
                                                <th>Data</th>
                                                <th>Status</th>
                                                <th style="width: 150px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Loading options...</td>
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                                <label class="form-label">Company</label>
                                <select name="category_id" class="form-select js-select2" data-placeholder="Select company" required>
                                    <option value="">Select company</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Sale Unit</span>
                                    @if ($showQuickUnit)
                                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add sale unit" data-quick-modal="#quickUnitModal" data-quick-target-select="[name='unit_sale_id']" data-unit-type="sales">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="unit_sale_id" class="form-select js-select2" data-placeholder="Select unit" required>
                                    <option value="">Select sale unit</option>
                                    @foreach ($saleUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Purchase Unit</span>
                                    @if ($showQuickUnit)
                                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add purchase unit" data-quick-modal="#quickUnitModal" data-quick-target-select="[name='unit_purchase_id']" data-unit-type="purchase">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="unit_purchase_id" class="form-select js-select2" data-placeholder="Select unit" required>
                                    <option value="">Select purchase unit</option>
                                    @foreach ($purchaseUnits as $unit)
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
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Formulation</span>
                                    @if ($showQuickDropdownOption)
                                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add formulation" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="[name='formulation_id']" data-dropdown-alias="formulation" data-dropdown-label="Formulation" data-dropdown-supports-data="0">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="formulation_id" class="form-select" data-dropdown-alias="formulation">
                                    <option value="">Select formulation</option>
                                    @foreach ($formulations as $formulation)
                                        <option value="{{ $formulation->id }}">{{ $formulation->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Product Status</span>
                                    @if ($showQuickDropdownOption)
                                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Quick add product status" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="[name='product_status_id']" data-dropdown-alias="product_status" data-dropdown-label="Product Status" data-dropdown-supports-data="0">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endif
                                </label>
                                <select name="product_status_id" class="form-select js-select2" data-placeholder="Select status" data-dropdown-alias="product_status">
                                    <option value="">Select status</option>
                                    @foreach ($productStatuses as $statusOption)
                                        <option value="{{ $statusOption->id }}">{{ $statusOption->name }}</option>
                                    @endforeach
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
                            <div class="col-md-12">
                                <label class="form-label">Usage Type</label>
                                <select name="type" class="form-select">
                                    <option value="both">Both</option>
                                    <option value="sales">Sales</option>
                                    <option value="purchase">Purchase</option>
                                </select>
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
