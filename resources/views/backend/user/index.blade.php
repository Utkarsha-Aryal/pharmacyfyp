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
                    <i class="fa fa-download"></i> Excel
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
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
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
                        <tbody>
                            @forelse ($users as $index => $listUser)
                                @php
                                    $userRole = $listUser->getRoleNames()->first() ?? 'staff';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $listUser->name }}</td>
                                    <td>{{ $listUser->email }}</td>
                                    <td>
                                        <span class="report-badge {{ $userRole === 'admin' ? 'report-badge-danger' : 'report-badge-success' }}">
                                            {{ ucfirst($userRole) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="report-badge {{ $listUser->is_active ? 'report-badge-success' : 'report-badge-danger' }}">
                                            {{ $listUser->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $listUser->created_at?->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="table-action-group">
                                            <a href="{{ route('admin.user.edit', $listUser) }}" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit User" aria-label="Edit User">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @if (auth()->id() !== $listUser->id)
                                                <form action="{{ route('admin.user.toggle-active', $listUser) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $listUser->is_active ? 'warning' : 'success' }} table-action-btn" title="{{ $listUser->is_active ? 'Deactivate User' : 'Activate User' }}" aria-label="{{ $listUser->is_active ? 'Deactivate User' : 'Activate User' }}">
                                                        <i class="fa-solid fa-{{ $listUser->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if (auth()->id() !== $listUser->id)
                                                <form action="{{ route('admin.user.delete', $listUser) }}" method="POST"
                                                    class="js-confirm-submit"
                                                    data-confirm-title="Delete this user?"
                                                    data-confirm-text="This account will be removed from the system."
                                                    data-confirm-button="Yes, delete user">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete User" aria-label="Delete User">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No users added yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
