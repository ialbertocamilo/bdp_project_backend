<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function App\helpers\OkResponse;

class UserAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return OkResponse(User::has('owners')->with('owners')->get(), 'Data retrieved.');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $slave_id = $request->slave;
        $owner_id = $request->owner;
        return OkResponse(User::find($owner_id)->slaves()->sync($slave_id));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\UserAssignment $userAssignment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        return OkResponse(auth()->user()->slaves);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\UserAssignment $userAssignment
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(UserAssignment $userAssignment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request   $request
     * @param \App\Models\UserAssignment $userAssignment
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserAssignment $userAssignment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\UserAssignment $userAssignment
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $slave_id)
    {

        UserAssignment::whereSlave($slave_id)->whereOwner(Auth::user()->id)->delete();
        return OkResponse('ok', 'Deleted.');
    }

    public function getMySlaves(){
        return OkResponse(auth()->user()->slaves,'Data retrieved.');
    }
}
