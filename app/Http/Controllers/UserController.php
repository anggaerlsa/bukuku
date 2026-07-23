<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);

        return view('manage.users.index', compact('users'));
    }

    public function create()
    {
        return view('manage.users.create', [
            'user' => new User(),
            'roles' => $this->assignableRoles(),
            'currentRole' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in($this->assignableRoles()->all())],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('status', "Pengguna \"{$user->name}\" ditambahkan.");
    }

    public function edit(User $user)
    {
        $this->guardSuperadminTarget($user);

        return view('manage.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->guardSuperadminTarget($user);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in($this->assignableRoles()->all())],
        ]);

        $user->name = $data['name'];
        $user->username = $data['username'] ?? null;
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('status', "Pengguna \"{$user->name}\" diperbarui.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $this->guardSuperadminTarget($user);

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('status', "Pengguna \"{$name}\" dihapus.");
    }

    /**
     * Roles the current actor is permitted to assign. Only a superadmin
     * may grant the superadmin role.
     */
    private function assignableRoles(): Collection
    {
        $roles = Role::orderBy('name')->pluck('name');

        if (! auth()->user()->hasRole('superadmin')) {
            $roles = $roles->reject(fn ($role) => $role === 'superadmin')->values();
        }

        return $roles;
    }

    /**
     * Only a superadmin may modify another superadmin.
     */
    private function guardSuperadminTarget(User $user): void
    {
        abort_if(
            $user->hasRole('superadmin') && ! auth()->user()->hasRole('superadmin'),
            403,
            'Hanya Raja Agung yang dapat mengubah sesama Raja Agung.'
        );
    }
}
