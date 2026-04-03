@extends('layouts.main')

@section('title')
    Manage Options
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Dropdown Options</h5>
                <p class="mb-0 text-muted">Manage all shared option values from one settings page.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Settings
                </a>
            </div>
        </div>

        @include('settings.partials.dropdown-options-manager', [
            'dropdownOptionAliases' => $dropdownOptionAliases,
            'dropdownOptionGroups' => $dropdownOptionGroups,
            'partyTypes' => $partyTypes,
            'supplierTypes' => $supplierTypes,
        ])

        @include('settings.partials.dropdown-options-modal')

        @include('partials.quick-create-modals', [
            'showQuickExpenseCategory' => false,
            'showQuickPaymentMode' => false,
            'showQuickCustomer' => false,
            'showQuickSupplier' => false,
            'showQuickProduct' => false,
            'showQuickUnit' => false,
            'showQuickPartyType' => true,
            'showQuickSupplierType' => true,
            'partyTypes' => $partyTypes,
            'supplierTypes' => $supplierTypes,
        ])
    </div>
@endsection
