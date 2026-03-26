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
                    <table class="table table-bordered align-middle">
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
                        <tbody>
                            @forelse ($roles as $index => $role)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ ucfirst($role->name) }}</div>
                                        @if ($role->name === 'admin')
                                            <small class="text-muted">System protected role</small>
                                        @endif
                                    </td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td>
                                        <div class="role-access-chip-wrap">
                                            @forelse ($role->permissions->take(4) as $permission)
                                                <span class="role-access-chip">
                                                    {{ ucwords(str_replace(['.', '_'], ' ', $permission->name)) }}
                                                </span>
                                            @empty
                                                <span class="text-muted">No permission selected.</span>
                                            @endforelse
                                            @if ($role->permissions->count() > 4)
                                                <span class="role-access-chip role-access-chip-muted">
                                                    +{{ $role->permissions->count() - 4 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.role-permission.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>
                                            @if ($role->name !== 'admin')
                                                <form action="{{ route('admin.role-permission.delete', $role) }}" method="POST"
                                                    class="js-confirm-submit"
                                                    data-confirm-title="Delete this role?"
                                                    data-confirm-text="Users must be moved out of this role before deletion."
                                                    data-confirm-button="Yes, delete role">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No roles added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
