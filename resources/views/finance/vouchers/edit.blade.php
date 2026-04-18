@extends('layouts.main')

@section('title')
    Edit Voucher
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Edit Voucher</h5>
                <p class="mb-0 text-muted">Update the voucher header and accounting lines together.</p>
            </div>
        </div>

        @include('finance.vouchers._form', [
            'formAction' => route('admin.finance.vouchers.update', $voucher),
            'submitLabel' => 'Update Voucher',
        ])
    </div>
@endsection
