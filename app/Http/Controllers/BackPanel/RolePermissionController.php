<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('backend.role-permission.index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return view('backend.role-permission.create', [
            'permissions' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create([
            'name' => Str::lower($validated['name']),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.role-permission.index')->with('success', 'Role added successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('backend.role-permission.edit', [
            'editRole' => $role,
            'permissions' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $rules = [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];

        if ($role->name === 'admin') {
            // admin role name is locked because middleware checks this exact name
            $rules['name'] = ['required', 'string', Rule::in(['admin'])];
        }

        $validated = $request->validate($rules);

        if ($role->name !== 'admin') {
            $role->name = Str::lower($validated['name']);
            $role->save();
        }

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.role-permission.index')->with('success', ucfirst($role->name) . ' permissions updated successfully.');
    }

    public function delete(Role $role)
    {
        if ($role->name === 'admin') {
            return redirect()->route('admin.role-permission.index')->with('error', 'Admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('admin.role-permission.index')->with('error', 'Please move users out of this role before deleting it.');
        }

        $role->delete();

        return redirect()->route('admin.role-permission.index')->with('success', 'Role deleted successfully.');
    }

    private function permissionGroups()
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                return Str::headline(explode('.', $permission->name)[0] ?? 'general');
            });
    }
}
