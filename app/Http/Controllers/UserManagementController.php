<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    // Show the main user list page.
    public function index()
    {
        return view('user.index', [
            'roles' => available_roles(),
        ]);
    }

    // Return user rows for the server-side DataTable.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $columns = (array) $request->input('columns', []);
        $nameKeyword = trim((string) data_get($columns, '1.search.value', ''));
        $emailKeyword = trim((string) data_get($columns, '2.search.value', ''));
        $roleKeyword = trim((string) data_get($columns, '3.search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = User::query()->with('roles')->orderByDesc('id');
        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhereHas('roles', function ($roleQuery) use ($keyword) {
                        $roleQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($nameKeyword !== '') {
            $query->where('name', 'like', '%' . $nameKeyword . '%');
        }

        if ($emailKeyword !== '') {
            $query->where('email', 'like', '%' . $emailKeyword . '%');
        }

        if ($roleKeyword !== '') {
            $query->whereHas('roles', function ($roleQuery) use ($roleKeyword) {
                $roleQuery->where('name', 'like', '%' . $roleKeyword . '%');
            });
        }

        $recordsFiltered = (clone $query)->count();
        $users = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($users as $index => $user) {
            $role = $user->getRoleNames()->first() ?? 'staff';
            $roleOptions = collect(available_roles())->map(function ($label, $value) use ($role) {
                return '<option value="' . e($value) . '"' . ($role === $value ? ' selected' : '') . '>' . e($label) . '</option>';
            })->implode('');
            $isCurrentUser = auth()->id() === $user->id;
            $statusLabel = $user->is_active ? 'Active' : 'Inactive';
            $statusClass = $user->is_active ? 'text-success' : 'text-danger';
            $toggleButtonClass = $user->is_active ? 'btn-outline-success' : 'btn-outline-danger';
            $toggleIcon = $user->is_active ? 'fa-toggle-on' : 'fa-toggle-off';
            $statusHtml = '<div class="d-flex align-items-center gap-2">';
            $statusHtml .= '<button type="button" class="btn btn-sm ' . $toggleButtonClass . ' table-action-btn status-toggle-btn js-user-status-toggle" data-url="' . route('admin.user.update-status', $user) . '" data-next-value="' . ($user->is_active ? '0' : '1') . '" data-confirm-title="' . e($user->is_active ? 'Deactivate this user?' : 'Activate this user?') . '" data-confirm-text="This will switch whether the account can login."' . ($isCurrentUser ? ' disabled' : '') . '>';
            $statusHtml .= '<i class="fa-solid ' . $toggleIcon . '"></i>';
            $statusHtml .= '</button>';
            $statusHtml .= '<span class="status-state-text ' . $statusClass . '">' . e($statusLabel) . '</span>';
            $statusHtml .= '</div>';

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.user.edit', $user) . '" class="btn btn-sm btn-outline-primary table-action-btn" title="Edit User" aria-label="Edit User"><i class="fa-solid fa-pen-to-square"></i></a>';

            if (! $isCurrentUser) {
                $action .= '<form action="' . route('admin.user.delete', $user) . '" method="POST" class="js-confirm-submit" data-confirm-title="Delete this user?" data-confirm-text="This account will be removed from the system." data-confirm-button="Yes, delete user">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete User" aria-label="Delete User"><i class="fa-solid fa-trash"></i></button></form>';
            }

            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'name' => e($user->name),
                'email' => e($user->email),
                'role' => '<div class="d-flex align-items-center gap-2 user-inline-select-wrap"><select class="form-select form-select-sm js-user-role-select" data-url="' . route('admin.user.update-role', $user) . '" data-current-value="' . e($role) . '"' . ($isCurrentUser ? ' disabled' : '') . '>' . $roleOptions . '</select><span class="report-badge ' . ($role === 'admin' ? 'report-badge-danger' : 'report-badge-success') . '">' . e(ucfirst($role)) . '</span></div>',
                'status' => $statusHtml,
                'created' => Carbon::parse($user->created_at)->format('M j, Y'),
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

    // Open the full create page for bigger user changes like password and email.
    public function create()
    {
        return view('user.create', [
            'roles' => available_roles(),
        ]);
    }

    // Save a new user from the full form.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(available_roles()))],
            'password' => ['required', 'confirmed', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // keep create flow simple for admin page
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.user.index')->with('success', 'User added successfully.');
    }

    // Open the full edit page.
    public function edit(User $user)
    {
        return view('user.edit', [
            'editUser' => $user,
            'roles' => available_roles(),
        ]);
    }

    // Update the user from the full edit form.
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(available_roles()))],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $request->boolean('is_active', true);

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.user.index')->with('success', 'User updated successfully.');
    }

    // Delete a user if it is not the currently logged in account.
    public function delete(User $user, Request $request)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account from here.');
        }

        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User deleted successfully.');
    }

    // Keep the old toggle endpoint for places where button-based status update is still used.
    public function toggleActive(User $user)
    {
        $user->is_active = ! (bool) $user->is_active;
        $user->save();

        return back()->with('success', 'User status updated successfully.');
    }

    // Quick role change from the list page so admin does not need to open the edit form for small updates.
    public function updateRole(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['type' => 'error', 'message' => 'You cannot change your own role from the list.'], 422);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(available_roles()))],
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'type' => 'success',
            'message' => 'User role updated successfully.',
        ]);
    }

    // Quick status change from the list page for faster admin work.
    public function updateStatus(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['type' => 'error', 'message' => 'You cannot change your own status from the list.'], 422);
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->is_active = (bool) $validated['is_active'];
        $user->save();

        return response()->json([
            'type' => 'success',
            'message' => 'User status updated successfully.',
        ]);
    }
}
