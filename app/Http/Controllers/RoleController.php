<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role as RequestsRole;
use App\Http\Resources\RoleCollection;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

use function App\helpers\OkResponse;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::paginate(10);
        return new RoleCollection($roles);
    }

    public function store(RequestsRole $request)
    {
        $role = new Role();
        $role->name = $request->name;
        $role->save();
        return OkResponse($role, 'Rol Guardado Correctamente', 201);
    }

    public function show(Role $role)
    {
        return new RoleResource($role);
    }

    public function update(RequestsRole $request, Role $role)
    {
        $role->name = $request->name;
        $role->update();
        return OkResponse($role, 'Rol Guardado Correctamente', 201);
    }
}
