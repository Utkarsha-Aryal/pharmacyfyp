@extends('layouts.main')

@section('title')
    New Purchase Return
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    @include('purchase-return.partials.form', [
        'pageHeading' => 'New Purchase Return',
        'pageDescription' => 'Use the same row-based entry flow as invoice pages while keeping both bill and product return logic.',
        'formAction' => route('admin.purchase-returns.store'),
        'submitLabel' => 'Save Purchase Return',
        'purchaseReturn' => null,
        'selectedPurchaseOption' => null,
        'suppliers' => $suppliers,
        'supplierTypes' => $supplierTypes ?? collect(),
        'itemsRows' => [],
    ])
@endsection

@section('script')
    @include('purchase-return.partials.script', [
        'purchaseReturn' => null,
    ])
@endsection
