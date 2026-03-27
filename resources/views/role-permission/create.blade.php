@extends('layouts.main')

@section('title')
    Add Role
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Add Role</h5>
                <p class="mb-0 text-muted">Create a new backend role and choose what it can access.</p>
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
            <form action="{{ route('admin.role-permission.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @include('role-permission._form')
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Role
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
