<?php

use App\Exports\ProjectsExport;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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

    Auth::attempt(['email'=>$request->email,'password'=>$request->password]);
    if (Auth::check()) {
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


Route::group(['prefix'=>'project','middleware'=>'auth:sanctum,role:Gestor|Supervisor|Auditor'],function(){
   Route::get('get-contents/{step}/{substep}/{uid}',[\App\Http\Controllers\ProjectController::class,'getAllContents']) ;
});

//PROJECT
Route::get('project',[\App\Http\Controllers\ProjectController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('project',[\App\Http\Controllers\ProjectController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('project/{project}',[\App\Http\Controllers\ProjectController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('project/{project}',[\App\Http\Controllers\ProjectController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('project/{project}',[\App\Http\Controllers\ProjectController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::delete('project/{project}',[\App\Http\Controllers\ProjectController::class])->middleware(['auth:sanctum','role:Gestor|Supervisor']);


Route::group(['prefix'=>'file-data','middleware'=>'auth:sanctum,role:Gestor|Supervisor|Auditor'],function(){
    Route::get('public/{fileName}',[\App\Http\Controllers\FileDataController::class,'downloadPublicFile']) ;

});

Route::get('file-data',[\App\Http\Controllers\FileDataController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('file-data',[\App\Http\Controllers\FileDataController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('file-data/{file_datum}',[\App\Http\Controllers\FileDataController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('file-data/{file_datum}',[\App\Http\Controllers\FileDataController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('file-data/{file_datum}',[\App\Http\Controllers\FileDataController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::delete('file-data/{file_datum}',[\App\Http\Controllers\FileDataController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::post('table-activities/{uid}',[\App\Http\Controllers\ProjectController::class,'editTableActivititesImplementation'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);


Route::get('time-line',[\App\Http\Controllers\TimeLineController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('time-line',[\App\Http\Controllers\TimeLineController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('time-line/{id}',[\App\Http\Controllers\TimeLineController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('time-line/{id}',[\App\Http\Controllers\TimeLineController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('time-line/{id}',[\App\Http\Controllers\TimeLineController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::destroy('time-line/{id}',[\App\Http\Controllers\TimeLineController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('budget',[\App\Http\Controllers\BudgetController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('budget',[\App\Http\Controllers\BudgetController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('budget/{id}',[\App\Http\Controllers\BudgetController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('budget/{id}',[\App\Http\Controllers\BudgetController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('budget/{id}',[\App\Http\Controllers\BudgetController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::destroy('budget/{id}',[\App\Http\Controllers\BudgetController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('edt',[\App\Http\Controllers\EDTController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('edt',[\App\Http\Controllers\EDTController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('edt/{id}',[\App\Http\Controllers\EDTController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('edt/{id}',[\App\Http\Controllers\EDTController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('edt/{id}',[\App\Http\Controllers\EDTController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::destroy('edt/{id}',[\App\Http\Controllers\EDTController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('acquisition',[\App\Http\Controllers\AcquisitionController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('acquisition',[\App\Http\Controllers\AcquisitionController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('acquisition/{id}',[\App\Http\Controllers\AcquisitionController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('acquisition/{id}',[\App\Http\Controllers\AcquisitionController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('acquisition/{id}',[\App\Http\Controllers\AcquisitionController::class,'put'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::delete('acquisition/{id}',[\App\Http\Controllers\AcquisitionController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('risk',[\App\Http\Controllers\RiskController::class,'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('risk',[\App\Http\Controllers\RiskController::class,'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('risk',[\App\Http\Controllers\RiskController::class,'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('risk',[\App\Http\Controllers\RiskController::class,'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('risk',[\App\Http\Controllers\RiskController::class,'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::delet('risk',[\App\Http\Controllers\RiskController::class,'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'index'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::post('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'store'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::get('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'edit'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::get('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'show'])->middleware(['auth:sanctum','role:Gestor|Supervisor|Auditor']);
Route::put('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'update'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);
Route::delete('responsability',[\App\Http\Controllers\ResponsabilityController::class, 'destroy'])->middleware(['auth:sanctum','role:Gestor|Supervisor']);

Route::get('/export-projects', function () {
    return Excel::download(new ProjectsExport, 'users.xlsx');
})->middleware(['auth:sanctum','Gestor|Supervisor|Auditor']);

Route::apiResource('users', UserController::class)->middleware(['auth:sanctum','role:Supervisor']);
Route::apiResource('roles', RoleController::class)->middleware(['auth:sanctum','role:Supervisor']);
