@extends('backend.layouts.main')

@section('title')
    Add User
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Add User</h5>
                <p class="mb-0 text-muted">Create a new admin or staff account from backend.</p>
            </div>
            <div class="d-flex my-xl-auto right-content">
                <a href="{{ route('admin.user.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">User Form</div>
                    </div>
                    <form action="{{ route('admin.user.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            @include('backend.user._form')
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
