<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login',function(Request $request){

    $auth=\Illuminate\Support\Facades\Auth::attempt($request->all());
    if ($auth) {
        $token = (object)$request->user()->createToken('bdp_token');
        $token=$token->plainTextToken;
        $message="Successfully.";
        return response()->json(compact('token','message'),202);
    }
    return response()->json(['error'=>'credentials error'],401);
});

Route::group(['prefix'=>'project','middleware'=>'auth:sanctum'],function(){
   Route::get('get-contents/{step}/{substep}/{uid}',[\App\Http\Controllers\ProjectController::class,'getAllContents']) ;
});

Route::resource('project',\App\Http\Controllers\ProjectController::class)->middleware('auth:sanctum');
Route::resource('file-data',\App\Http\Controllers\FileDataController::class)->middleware('auth:sanctum');

Route::post('table-activities/{uid}',[\App\Http\Controllers\ProjectController::class,'editTableActivititesImplementation']);
