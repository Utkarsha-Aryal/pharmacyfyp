<?php

namespace App\Http\Controllers\BackPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        return view('backend.user.index', [
            'users' => User::orderByDesc('id')->get(),
            'roles' => available_roles(),
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
        ]);

        // keep create flow simple for admin page
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
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
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

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
}
