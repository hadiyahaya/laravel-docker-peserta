<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users, with search and pagination.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->with('roles')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    /**
     * Save the new role.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRoles($request->user()))],
        ]);

        $user->syncRoles([$validated['role']]);

        return to_route('users.index')->with('status', __('Role updated.'));
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        Gate::authorize('deleteAdmin', $user);
        $user->delete();

        return to_route('users.index')->with('status', __('User deleted.'));
    }

    /**
     * Get the roles that the current user can assign to other users.
     */
    private function assignableRoles(User $user): array
    {
        return Role::query()
            ->when(! $user->hasRole('super-admin'), fn ($query) => 
                $query->where('name', '!=', 'super-admin'))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

}
