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
        return view('backend.role-permission.index', [
        ]);
    }

    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = Role::query()->with('permissions')->withCount('users')->orderBy('name');
        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhereHas('permissions', function ($permissionQuery) use ($keyword) {
                        $permissionQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();
        $roles = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($roles as $index => $role) {
            $summary = $role->permissions->take(4)->map(function ($permission) {
                return '<span class="role-access-chip">' . e(ucwords(str_replace(['.', '_'], ' ', $permission->name))) . '</span>';
            })->implode('');

            if ($summary === '') {
                $summary = '<span class="text-muted">No permission selected.</span>';
            } elseif ($role->permissions->count() > 4) {
                $summary .= '<span class="role-access-chip role-access-chip-muted">+' . ($role->permissions->count() - 4) . ' more</span>';
            }

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.role-permission.edit', $role) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit Role" aria-label="Edit Role"><i class="fa-solid fa-pen-to-square"></i></a>';

            if ($role->name !== 'admin') {
                $action .= '<form action="' . route('admin.role-permission.delete', $role) . '" method="POST" class="js-confirm-submit" data-confirm-title="Delete this role?" data-confirm-text="Users must be moved out of this role before deletion." data-confirm-button="Yes, delete role">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Role" aria-label="Delete Role"><i class="fa-solid fa-trash"></i></button></form>';
            }

            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'role' => '<div class="fw-semibold">' . e(ucfirst($role->name)) . '</div>' . ($role->name === 'admin' ? '<small class="text-muted">System protected role</small>' : ''),
                'users' => $role->users_count,
                'permissions' => $role->permissions->count(),
                'access_summary' => '<div class="role-access-chip-wrap">' . $summary . '</div>',
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
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
