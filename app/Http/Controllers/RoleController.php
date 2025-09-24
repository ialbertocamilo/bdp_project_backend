<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use CacheableTrait;

    private $permissions = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'users.manage',
        'projects.view',
        'projects.create',
        'projects.edit',
        'projects.delete',
        'projects.import',
        'tasks.view',
        'tasks.create',
        'tasks.edit',
        'tasks.delete',
        'reports.view',
        'reports.generate',
        'dashboard.view',
        'dashboard.edit',
        'settings.view',
        'settings.edit',
        'roles.view',
        'roles.edit',
        'roles.manage',
        'system.manage'
    ];

    public function index()
    {
        $roles = $this->remember('roles_list', 600, function () {
            return Role::withCount('users')->get();
        });

        return response()->json([
            'roles' => $roles,
            'available_permissions' => $this->permissions
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permissions = array_intersect($request->permissions ?? [], $this->permissions);

        $role = Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'permissions' => $permissions
        ]);

        $this->forget('roles_list');

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    public function show(Role $role)
    {
        return response()->json([
            'role' => $role->load('users')
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permissions = array_intersect($request->permissions ?? [], $this->permissions);

        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'permissions' => $permissions
        ]);

        $this->forget('roles_list');

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete role with assigned users'
            ], 422);
        }

        $role->delete();
        $this->forget('roles_list');

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function permissions()
    {
        return response()->json([
            'permissions' => $this->permissions
        ]);
    }
}