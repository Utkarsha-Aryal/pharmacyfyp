@extends('backend.layouts.main')

@section('title')
    Users
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Users</h5>
                <p class="mb-0 text-muted">Admin can create staff, update roles and manage access here.</p>
            </div>
            <div class="d-flex my-xl-auto right-content gap-2">
                <a href="{{ route('admin.export.user') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.user.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add User
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">User List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="userTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.user.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width: 260px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            window.userTable = window.initServerSideDataTable({
                selector: '#userTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'role' },
                    { data: 'status' },
                    { data: 'created' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.user.list') }}'
            });
        });
    </script>
@endsection
