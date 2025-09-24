<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use CacheableTrait;

    public function index(Request $request)
    {
        $users = $this->remember('users_list', 300, function () use ($request) {
            $query = User::with(['roles', 'twoFactorAuth']);
            
            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }
            
            if ($request->has('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('slug', $request->role);
                });
            }
            
            return $query->paginate(15);
        });

        // Formatear la respuesta para incluir los roles correctamente
        $formattedUsers = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first() ? $user->roles->first()->name : 'Sin rol',
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug
                    ];
                }),
                'department' => $user->department ?? null,
                'position' => $user->position ?? null,
                'avatar' => $user->avatar ?? null,
                'phone' => $user->phone ?? null,
                'status' => $user->status,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $formattedUsers,
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'roles' => 'array|min:1' // Requerir al menos un rol
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active', // Estado activo por defecto
            'is_active' => true
        ]);

        // Asignar roles (requerido)
        if ($request->has('roles') && !empty($request->roles)) {
            foreach ($request->roles as $roleId) {
                $user->assignRole($roleId);
            }
        } else {
            // Si no se especifican roles, asignar rol de usuario por defecto
            $defaultRole = Role::where('slug', 'user')->first();
            if ($defaultRole) {
                $user->assignRole('user');
            }
        }

        $this->forget('users_list');

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['roles'])
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['roles', 'twoFactorAuth', 'projects'])
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'status' => 'sometimes|in:active,inactive',
            'roles' => 'array|min:1' // Requerir al menos un rol
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except(['password', 'roles']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Validar que siempre tenga al menos un rol
        if ($request->has('roles')) {
            if (empty($request->roles)) {
                return response()->json([
                    'error' => 'El usuario debe tener al menos un rol asignado'
                ], 422);
            }
            
            $user->roles()->detach();
            foreach ($request->roles as $roleId) {
                $user->assignRole($roleId);
            }
        }

        $this->forget('users_list');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load(['roles'])
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot delete yourself'], 403);
        }

        $user->roles()->detach();
        $user->delete();

        $this->forget('users_list');

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function enable2FA(Request $request, User $user)
    {
        $backupCodes = $user->enable2FA();
        
        return response()->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes,
            'secret_key' => $user->twoFactorAuth->secret_key
        ]);
    }

    public function disable2FA(User $user)
    {
        $user->disable2FA();
        
        return response()->json(['message' => '2FA disabled successfully']);
    }

    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $token = bin2hex(random_bytes(32));
        
        return response()->json([
            'message' => 'Password reset link sent',
            'reset_token' => $token
        ]);
    }
}