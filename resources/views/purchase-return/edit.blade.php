@extends('layouts.main')

@section('title')
    Edit Purchase Return
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    @include('purchase-return.partials.form', [
        'pageHeading' => 'Edit Purchase Return',
        'pageDescription' => 'Edit purchase returns with the same row-by-row flow used on invoice pages.',
        'formAction' => route('admin.purchase-returns.update', $purchaseReturn),
        'submitLabel' => 'Update Purchase Return',
        'purchaseReturn' => $purchaseReturn,
        'selectedPurchaseOption' => $purchaseReturn->purchase_id ? [
            'id' => $purchaseReturn->purchase_id,
            'text' => ($purchaseReturn->purchase?->reference?->reference_no ?: ('PUR-' . $purchaseReturn->purchase_id)) . ' | ' . ($purchaseReturn->purchase?->purchase_date_show ?? '-') . ' | ' . money_value($purchaseReturn->purchase?->grand_total ?? 0),
        ] : null,
        'suppliers' => $suppliers,
        'supplierTypes' => $supplierTypes ?? collect(),
        'itemsRows' => $itemsRows,
    ])
@endsection

@section('script')
    @include('purchase-return.partials.script', [
        'purchaseReturn' => $purchaseReturn,
    ])
@endsection
