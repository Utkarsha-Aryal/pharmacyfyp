<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('backend.user.index', [
            'roles' => available_roles(),
        ]);
    }

    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
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

        $recordsFiltered = (clone $query)->count();
        $users = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($users as $index => $user) {
            $role = $user->getRoleNames()->first() ?? 'staff';
            $isCurrentUser = auth()->id() === $user->id;
            $statusLabel = $user->is_active ? 'Active' : 'Inactive';
            $statusClass = $user->is_active ? 'text-success' : 'text-secondary';
            $toggleClass = $user->is_active ? 'btn-outline-success' : 'btn-outline-secondary';
            $toggleIcon = $user->is_active ? 'fa-toggle-on' : 'fa-toggle-off';
            $toggleTitle = $user->is_active ? 'Deactivate User' : 'Activate User';

            $statusHtml = '<div class="d-inline-flex align-items-center gap-2">';

            if (! $isCurrentUser) {
                $statusHtml .= '<form action="' . route('admin.user.toggle-active', $user) . '" method="POST" class="js-confirm-submit" data-confirm-title="Change user status?" data-confirm-text="This will switch the account access." data-confirm-button="Yes, update status">' . csrf_field() . '<button type="submit" class="btn btn-sm ' . $toggleClass . ' table-action-btn status-toggle-btn" title="' . e($toggleTitle) . '" aria-label="' . e($toggleTitle) . '"><i class="fa-solid ' . $toggleIcon . '"></i></button></form>';
            }

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
                'role' => '<span class="report-badge ' . ($role === 'admin' ? 'report-badge-danger' : 'report-badge-success') . '">' . e(ucfirst($role)) . '</span>',
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

    public function create()
    {
        return view('backend.user.create', [
            'roles' => available_roles(),
        ]);
    }

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

    public function edit(User $user)
    {
        return view('backend.user.edit', [
            'editUser' => $user,
            'roles' => available_roles(),
        ]);
    }

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

    public function delete(User $user, Request $request)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account from here.');
        }

        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User deleted successfully.');
    }

    public function toggleActive(User $user)
    {
        $user->is_active = ! (bool) $user->is_active;
        $user->save();

        return back()->with('success', 'User status updated successfully.');
    }
}
