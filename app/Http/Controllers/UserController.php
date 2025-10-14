<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status', 'active'); // active, deleted, all
        $role = $request->get('role');

        $query = User::query()
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            });

        // Handle soft delete filter
        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::all(['id', 'name']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $roles = Role::all(['id', 'name']);
        
        return Inertia::render('Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now(); // Auto-verify admin created users

        $user = User::create($validated);
        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->load('roles');

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $user->load('roles');
        $roles = Role::all(['id', 'name']);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        // Only update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Update role
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent superadmin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deactivated successfully.');
    }

    /**
     * Restore a soft deleted user.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('users.index')->with('success', 'User restored successfully.');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        // Find user whether active or soft-deleted
        $user = User::withTrashed()->findOrFail($id);
        
        // Prevent superadmin from permanently deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot permanently delete your own account.');
        }

        $user->forceDelete();

        return redirect()->route('users.index')->with('success', 'User permanently deleted.');
    }
}
