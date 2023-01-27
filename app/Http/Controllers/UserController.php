<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserEditRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use function App\helpers\OkResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //return Auth::user()->role;
        $user = User::paginate(10);
        return new UserCollection($user);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            if (isset($request->role)) {
                $user->syncRoles($request->role);
            }
            $user->save();
            return OkResponse($user, 'Usuario Guardado Correctamente', 201);
        } catch (\Throwable $th) {
            return response()->json(["message" => 'Error.' . $th->getMessage()], 402);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserEditRequest $request, User $user)
    {
        try {
            $user->name = $request->name;
            $user->email = $request->email;
            if (isset($request->password)) {
                $user->password = bcrypt($request->password);
            }
            if (isset($request->role)) {
                $user->syncRoles($request->role);
            }
            $user->update();
            return OkResponse($user, 'Usuario Actualizado Correctamente');
        } catch (\Throwable $th) {
            return response()->json(["message" => 'Error.' . $th->getMessage()], 402);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return OkResponse($user, 'Usuario Eliminado Correctamente', 204);
    }

    public function getUsersByRoleId(int $id){


        return OkResponse(Role::findById($id),'Usuarios con role id = '.$id);
    }
}
