@extends('layouts.main')

@section('title')
    Edit Sales Return
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Edit Sales Return</h5>
                <p class="mb-0 text-muted">Update the invoice item, quantity, date, or refund and keep stock history in sync.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @include('sales.returns._form', [
            'formAction' => route('admin.sales.returns.update', $salesReturn),
            'submitLabel' => 'Update Sales Return',
            'showDeleteButton' => true,
        ])
    </div>
@endsection
