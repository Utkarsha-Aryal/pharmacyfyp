@extends('backend.layouts.main')

@section('title')
    Edit Role
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Edit Role</h5>
                <p class="mb-0 text-muted">Update role details and control access from one page.</p>
            </div>
            <div class="d-flex my-xl-auto right-content">
                <a href="{{ route('admin.role-permission.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Role Form</div>
            </div>
            <form action="{{ route('admin.role-permission.update', $editRole) }}" method="POST">
                @csrf
                <div class="card-body">
                    @include('backend.role-permission._form', ['editRole' => $editRole])
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Update Role
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
