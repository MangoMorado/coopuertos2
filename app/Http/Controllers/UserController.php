<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'html' => view('users.partials.table', compact('users'))->render(),
                'pagination' => view('users.partials.pagination', compact('users'))->render(),
            ]);
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $user = auth()->user();
        $roles = collect();

        // Mango puede crear User, Admin y Mango
        if ($user->hasRole('Mango')) {
            $roles = Role::whereIn('name', ['User', 'Admin', 'Mango'])->where('guard_name', 'web')->get();
        }
        // Admin solo puede crear User
        elseif ($user->hasRole('Admin')) {
            $roles = Role::where('name', 'User')->where('guard_name', 'web')->get();
        }

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:User,Admin,Mango',
            'theme' => ['nullable', 'string', 'in:light,dark'],
        ]);

        // Verificar permisos según rol
        if ($user->hasRole('Admin')) {
            // Admin solo puede crear usuarios con rol User
            if ($validated['role'] !== 'User') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No tienes permisos para crear usuarios con ese rol.');
            }
        }
        // Mango puede crear cualquier rol, no necesita validación adicional

        // Crear usuario
        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'theme' => $validated['theme'] ?? 'light',
        ]);

        // Asignar rol
        $newUser->assignRole($validated['role']);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        $authUser = auth()->user();
        $roles = collect();

        // Mango puede editar cualquier usuario y asignar cualquier rol
        if ($authUser->hasRole('Mango')) {
            $roles = Role::whereIn('name', ['User', 'Admin', 'Mango'])->where('guard_name', 'web')->get();
        }
        // Admin solo puede editar usuarios User
        elseif ($authUser->hasRole('Admin')) {
            if (! $user->hasRole('User')) {
                return redirect()->route('usuarios.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
            $roles = Role::where('name', 'User')->where('guard_name', 'web')->get();
        }

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:User,Admin,Mango',
            'theme' => ['nullable', 'string', 'in:light,dark'],
        ]);

        // Verificar permisos según rol
        if ($authUser->hasRole('Admin')) {
            // Admin solo puede editar usuarios User
            if (! $user->hasRole('User')) {
                return redirect()->route('usuarios.index')
                    ->with('error', 'No tienes permisos para editar este usuario.');
            }
            // Admin solo puede asignar rol User
            if ($validated['role'] !== 'User') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No tienes permisos para asignar ese rol.');
            }
        }

        // Actualizar usuario
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (isset($validated['theme'])) {
            $updateData['theme'] = $validated['theme'];
        }

        $user->update($updateData);

        // Actualizar contraseña si se proporciona
        if (isset($validated['password']) && $validated['password']) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Actualizar rol
        $user->syncRoles([$validated['role']]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        // No permitir eliminar a uno mismo
        if ($user->id === auth()->id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $authUser = auth()->user();

        // Verificar permisos
        if ($authUser->hasRole('Admin')) {
            // Admin solo puede eliminar usuarios User
            if (! $user->hasRole('User')) {
                return redirect()->route('usuarios.index')
                    ->with('error', 'No tienes permisos para eliminar este usuario.');
            }
        }

        $user->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
