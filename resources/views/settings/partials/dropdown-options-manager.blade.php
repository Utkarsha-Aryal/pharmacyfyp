@php
    $dropdownOptionAliases = $dropdownOptionAliases ?? [];
    $dropdownOptionGroups = $dropdownOptionGroups ?? collect();
    $partyTypes = $partyTypes ?? collect();
    $supplierTypes = $supplierTypes ?? collect();
@endphp

<div class="card custom-card" id="dropdown-option-manager">
    <div class="card-header justify-content-between">
        <div>
            <div class="card-title">Manage Options</div>
            <small class="text-muted">Shared dropdown values for product status, formulation, sales type, payment mode and expense category.</small>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-pills flex-wrap gap-2 mb-4" role="tablist">
            @foreach ($dropdownOptionAliases as $alias => $meta)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#dropdown-alias-{{ $alias }}" type="button" role="tab">
                        {{ $meta['label'] }}
                    </button>
                </li>
            @endforeach
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#dropdown-alias-party-type" type="button" role="tab">
                    Party Type
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#dropdown-alias-supplier-type" type="button" role="tab">
                    Supplier Type
                </button>
            </li>
        </ul>

        <div class="tab-content">
            @foreach ($dropdownOptionAliases as $alias => $meta)
                @php
                    $rows = $dropdownOptionGroups->get($alias, collect());
                @endphp
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dropdown-alias-{{ $alias }}" role="tabpanel">
                    <div class="card border">
                        <div class="card-header justify-content-between">
                            <div>
                                <div class="card-title mb-0">{{ $meta['label'] }}</div>
                                <small class="text-muted">Alias: <code>{{ $alias }}</code></small>
                            </div>
                            <button
                                type="button"
                                class="btn btn-success btn-sm js-dropdown-option-add"
                                data-bs-toggle="tooltip"
                                title="Add {{ strtolower($meta['label']) }}"
                                data-dropdown-alias="{{ $alias }}"
                                data-dropdown-label="{{ $meta['label'] }}"
                                data-dropdown-supports-data="{{ $meta['supports_data'] ? 1 : 0 }}">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0 dropdown-option-table" data-dropdown-alias="{{ $alias }}">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;">S.No</th>
                                            <th>Name</th>
                                            <th>Alias</th>
                                            <th>Data</th>
                                            <th>Status</th>
                                            <th style="width: 160px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rows as $index => $option)
                                            <tr data-id="{{ $option->id }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td class="dropdown-option-name">{{ $option->name }}</td>
                                                <td><code>{{ $option->alias }}</code></td>
                                                <td class="dropdown-option-data">{{ $option->data ?: '-' }}</td>
                                                <td class="dropdown-option-status">
                                                    <span class="badge {{ $option->status ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $option->status ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-action-group">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary table-action-btn js-dropdown-option-edit"
                                                            title="Edit"
                                                            data-id="{{ $option->id }}"
                                                            data-dropdown-alias="{{ $alias }}"
                                                            data-dropdown-label="{{ $meta['label'] }}"
                                                            data-dropdown-supports-data="{{ $meta['supports_data'] ? 1 : 0 }}"
                                                            data-name="{{ $option->name }}"
                                                            data-data="{{ $option->data }}"
                                                            data-status="{{ $option->status }}">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm {{ $option->status ? 'btn-outline-warning' : 'btn-outline-success' }} table-action-btn js-dropdown-option-toggle"
                                                            title="Toggle"
                                                            data-id="{{ $option->id }}"
                                                            data-dropdown-alias="{{ $alias }}"
                                                            data-name="{{ $option->name }}"
                                                            data-status="{{ $option->status }}">
                                                            <i class="fa-solid {{ $option->status ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger table-action-btn js-dropdown-option-delete"
                                                            title="Delete"
                                                            data-id="{{ $option->id }}"
                                                            data-dropdown-alias="{{ $alias }}"
                                                            data-name="{{ $option->name }}">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No options added yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="tab-pane fade" id="dropdown-alias-party-type" role="tabpanel">
                <div class="card border">
                    <div class="card-header justify-content-between">
                        <div>
                            <div class="card-title mb-0">Party Type</div>
                            <small class="text-muted">Reusable party labels for customer and institution flows.</small>
                        </div>
                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Add party type" data-quick-modal="#quickPartyTypeModal">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0" id="partyTypeTable">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">S.No</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                        <th style="width: 160px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($partyTypes as $index => $partyType)
                                        <tr data-id="{{ $partyType->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td class="party-type-name">{{ $partyType->name }}</td>
                                            <td class="party-type-code"><code>{{ $partyType->code }}</code></td>
                                            <td class="party-type-status">
                                                <span class="badge {{ $partyType->is_active ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $partyType->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-action-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickPartyTypeEditBtn" data-id="{{ $partyType->id }}" data-name="{{ $partyType->name }}" title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm {{ $partyType->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} table-action-btn quickPartyTypeToggleBtn" data-id="{{ $partyType->id }}" data-active="{{ $partyType->is_active ? 1 : 0 }}" title="Toggle">
                                                        <i class="fa-solid {{ $partyType->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickPartyTypeDeleteBtn" data-id="{{ $partyType->id }}" title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No party types added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="dropdown-alias-supplier-type" role="tabpanel">
                <div class="card border">
                    <div class="card-header justify-content-between">
                        <div>
                            <div class="card-title mb-0">Supplier Type</div>
                            <small class="text-muted">Reusable supplier labels for purchase and payment flows.</small>
                        </div>
                        <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-bs-toggle="tooltip" title="Add supplier type" data-quick-modal="#quickSupplierTypeModal">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0" id="supplierTypeTable">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">S.No</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                        <th style="width: 160px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($supplierTypes as $index => $supplierType)
                                        <tr data-id="{{ $supplierType->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td class="supplier-type-name">{{ $supplierType->name }}</td>
                                            <td class="supplier-type-code"><code>{{ $supplierType->code }}</code></td>
                                            <td class="supplier-type-status">
                                                <span class="badge {{ $supplierType->is_active ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $supplierType->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="table-action-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickSupplierTypeEditBtn" data-id="{{ $supplierType->id }}" data-name="{{ $supplierType->name }}" title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm {{ $supplierType->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} table-action-btn quickSupplierTypeToggleBtn" data-id="{{ $supplierType->id }}" data-active="{{ $supplierType->is_active ? 1 : 0 }}" title="Toggle">
                                                        <i class="fa-solid {{ $supplierType->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickSupplierTypeDeleteBtn" data-id="{{ $supplierType->id }}" title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No supplier types added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
