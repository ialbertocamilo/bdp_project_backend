<?php

use App\Exports\ProjectsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

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

Route::post('/login', function (Request $request) {

    $auth=\Illuminate\Support\Facades\Auth::attempt($request->all());
    if ($auth) {
        $token = (object)$request->user()->createToken('bdp_token');
        $token=$token->plainTextToken;
        $message="Successfully.";
        return response()->json(compact('token','message'),202);
    }
    return response()->json(['error'=>'credentials error'],401);
});

Route::get('/test',function(){
   return response()->json("test data !");
});


Route::group(['prefix'=>'project','middleware'=>'auth:sanctum'],function(){
   Route::get('get-contents/{step}/{substep}/{uid}',[\App\Http\Controllers\ProjectController::class,'getAllContents']) ;
});

Route::resource('project',\App\Http\Controllers\ProjectController::class)->middleware('auth:sanctum');


Route::group(['prefix'=>'file-data','middleware'=>'auth:sanctum'],function(){
    Route::get('public/{fileName}',[\App\Http\Controllers\FileDataController::class,'downloadPublicFile']) ;

});
Route::resource('file-data',\App\Http\Controllers\FileDataController::class)->middleware('auth:sanctum');

Route::post('table-activities/{uid}',[\App\Http\Controllers\ProjectController::class,'editTableActivititesImplementation']);

Route::resource('time-line',\App\Http\Controllers\TimeLineController::class)->middleware('auth:sanctum');

Route::resource('budget',\App\Http\Controllers\BudgetController::class)->middleware('auth:sanctum');

Route::resource('edt',\App\Http\Controllers\EDTController::class)->middleware('auth:sanctum');

Route::resource('acquisition',\App\Http\Controllers\AcquisitionController::class)->middleware('auth:sanctum');

Route::resource('risk',\App\Http\Controllers\RiskController::class)->middleware('auth:sanctum');

Route::resource('responsability',\App\Http\Controllers\ResponsabilityController::class)->middleware('auth:sanctum');

Route::get('/export-projects', function () {
    return Excel::download(new ProjectsExport, 'users.xlsx');
});
