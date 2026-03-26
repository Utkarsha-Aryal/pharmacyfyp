@extends('backend.layouts.main')

@section('title')
    Role Access
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Role Access</h5>
                <p class="mb-0 text-muted">Create roles, update permissions, and remove old roles from one place.</p>
            </div>
            <div class="d-flex my-xl-auto right-content">
                <a href="{{ route('admin.role-permission.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Role
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Role List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="roleTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.role-permission.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Role</th>
                                <th>Users</th>
                                <th>Permissions</th>
                                <th>Access Summary</th>
                                <th style="width: 210px;">Action</th>
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
            window.roleTable = window.initServerSideDataTable({
                selector: '#roleTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'role' },
                    { data: 'users' },
                    { data: 'permissions' },
                    { data: 'access_summary' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.role-permission.list') }}'
            });
        });
    </script>
@endsection
