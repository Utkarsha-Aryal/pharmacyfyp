@php
    $selectedPermissions = collect(old('permissions', isset($editRole) ? $editRole->permissions->pluck('name')->all() : []));
    $roleName = old('name', $editRole->name ?? '');
    $isAdminRole = isset($editRole) && $editRole->name === 'admin';
@endphp

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border role-form-side-card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="role_name" class="form-label">Role Name <span class="required-field">*</span></label>
                    <input type="text" name="name" id="role_name" class="form-control"
                        value="{{ $roleName }}" placeholder="example: manager"
                        @readonly($isAdminRole)>
                    <small class="text-muted d-block mt-2">
                        Use small letters, numbers, underscore or dash only.
                    </small>
                    @if ($isAdminRole)
                        <small class="text-muted d-block mt-2">
                            Admin role name is locked because system access depends on it.
                        </small>
                    @endif
                </div>

                <div class="profile-note-box">
                    <strong>Permission note</strong>
                    <span>Select only the modules this role should open from </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row g-3">
            @foreach ($permissions as $groupTitle => $groupPermissions)
                <div class="col-lg-6">
                    <div class="card border permission-card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="card-title mb-0">{{ $groupTitle }}</div>
                            <span class="badge bg-light text-dark">{{ $groupPermissions->count() }}</span>
                        </div>
                        <div class="card-body">
                            @foreach ($groupPermissions as $permission)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                        value="{{ $permission->name }}"
                                        id="permission-{{ $permission->id }}-{{ $loop->parent->index }}"
                                        @checked($selectedPermissions->contains($permission->name))>
                                    <label class="form-check-label" for="permission-{{ $permission->id }}-{{ $loop->parent->index }}">
                                        {{ ucwords(str_replace(['.', '_'], ' ', $permission->name)) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
