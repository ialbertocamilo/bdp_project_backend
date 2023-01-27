<?php

use App\Exports\ProjectsExport;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    $auth = Auth::attempt(['email' => $request->email, 'password' => $request->password]);
    if ($auth) {
        $token   = (object)$request->user()->createToken('bdp_token');
        $token   = $token->plainTextToken;
        $message = "Successfully.";
        $roles   = '';
        if (count(Auth::user()->roles) > 0) {
            $roles = Auth::user()->roles[0]->name;
        }
        return response()->json(compact('token', 'roles', 'message'), 202);
    }
    return response()->json(['error' => 'credentials error'], 401);
});


Route::get('/logout', function (Request $request) {
    if (Auth::check()) {
        $request->user()->currentAccessToken()->delete();
    }

    return response()->json(["msg" =>Auth::check()]);
})->middleware("auth:sanctum");

const DOMAIN = "bdp.com.bo";
const DN     = "dc=bdp,dc=com,dc=bo";
Route::get('/test', function () {
    $user       = "rchiri";
    $pass       = "consultsdfsfssor.1";
    $ldaprdn    = trim($user) . '@' . DOMAIN;
    $ldappass   = trim($pass);
    $ds         = DOMAIN;
    $dn         = DN;
    $puertoldap = 389;
    $ldapconn   = ldap_connect($ds, $puertoldap);
    ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    $ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);
    if ($ldapbind) {
        $filter = "(|(SAMAccountName=" . trim($user) . "))";
        $fields = ["SAMAccountName"];
        $sr     = @ldap_search($ldapconn, $dn, $filter, $fields);
        $info   = @ldap_get_entries($ldapconn, $sr);
        $array  = $info[0]["samaccountname"][0];
    } else {
        $array = 0;
    }
    ldap_close($ldapconn);

    if ($array!=0 || $array!='')
        return response()->json("Logeado correctamente");
    else
        return response()->json("Usuario AD incorrecto");
    return response()->json("test data !");
});


Route::group(['prefix' => 'project', 'middleware' => 'auth:sanctum,role:Gestor|Supervisor|Auditor'], function () {
    Route::get('get-contents/{step}/{substep}/{uid}', [\App\Http\Controllers\ProjectController::class, 'getAllContents']);
});

//PROJECT
Route::get('project', [\App\Http\Controllers\ProjectController::class, 'index'])->middleware(['auth:sanctum', 'role:Supervisor|Gestor|Auditor']);
Route::post('project', [\App\Http\Controllers\ProjectController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('project/{project}', [\App\Http\Controllers\ProjectController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('project/{project}', [\App\Http\Controllers\ProjectController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('project/{project}', [\App\Http\Controllers\ProjectController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('project/{project}', [\App\Http\Controllers\ProjectController::class])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('project/{uuid}/get-status/', [\App\Http\Controllers\ProjectController::class,'getProjectStatus'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);


Route::group(['prefix' => 'file-data', 'middleware' => 'auth:sanctum,role:Gestor|Supervisor|Auditor'], function () {
    Route::get('public/{fileName}', [\App\Http\Controllers\FileDataController::class, 'downloadPublicFile']);

});

Route::get('file-data', [\App\Http\Controllers\FileDataController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('file-data', [\App\Http\Controllers\FileDataController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('file-data/{file_datum}', [\App\Http\Controllers\FileDataController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('file-data/{file_datum}', [\App\Http\Controllers\FileDataController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('file-data/{file_datum}', [\App\Http\Controllers\FileDataController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('file-data/{file_datum}', [\App\Http\Controllers\FileDataController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::post('table-activities/{uid}', [\App\Http\Controllers\ProjectController::class, 'editTableActivititesImplementation'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);


Route::get('time-line', [\App\Http\Controllers\TimeLineController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('time-line', [\App\Http\Controllers\TimeLineController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('time-line/{id}', [\App\Http\Controllers\TimeLineController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('time-line/{id}', [\App\Http\Controllers\TimeLineController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('time-line/{id}', [\App\Http\Controllers\TimeLineController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('time-line/{id}', [\App\Http\Controllers\TimeLineController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('budget', [\App\Http\Controllers\BudgetController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('budget', [\App\Http\Controllers\BudgetController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('budget/{id}', [\App\Http\Controllers\BudgetController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('budget/{id}', [\App\Http\Controllers\BudgetController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('budget/{id}', [\App\Http\Controllers\BudgetController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('budget/{id}', [\App\Http\Controllers\BudgetController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('edt', [\App\Http\Controllers\EDTController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('edt', [\App\Http\Controllers\EDTController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('edt/{id}', [\App\Http\Controllers\EDTController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('edt/{id}', [\App\Http\Controllers\EDTController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('edt/{id}', [\App\Http\Controllers\EDTController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('edt/{id}', [\App\Http\Controllers\EDTController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('acquisition', [\App\Http\Controllers\AcquisitionController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('acquisition', [\App\Http\Controllers\AcquisitionController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('acquisition/{id}', [\App\Http\Controllers\AcquisitionController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('acquisition/{id}', [\App\Http\Controllers\AcquisitionController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('acquisition/{id}', [\App\Http\Controllers\AcquisitionController::class, 'put'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('acquisition/{id}', [\App\Http\Controllers\AcquisitionController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('risk', [\App\Http\Controllers\RiskController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('risk', [\App\Http\Controllers\RiskController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('risk', [\App\Http\Controllers\RiskController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('risk', [\App\Http\Controllers\RiskController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('risk', [\App\Http\Controllers\RiskController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('risk', [\App\Http\Controllers\RiskController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'index'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::post('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'store'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::get('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'edit'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::get('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'show'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);
Route::put('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'update'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);
Route::delete('responsability', [\App\Http\Controllers\ResponsabilityController::class, 'destroy'])->middleware(['auth:sanctum', 'role:Gestor|Supervisor']);

Route::get('/export-projects', function () {
    return Excel::download(new ProjectsExport, 'users.xlsx');
})->middleware(['auth:sanctum', 'role:Gestor|Supervisor|Auditor']);

Route::apiResource('users', UserController::class)->middleware(['auth:sanctum', 'role:Supervisor|Gestor']);

Route::get('users/role/{id}',[UserController::class,'getUsersByRoleId']);
Route::apiResource('roles', RoleController::class)->middleware(['auth:sanctum', 'role:Supervisor']);

Route::get('close-project/{uuid}', [ProjectController::class, 'closeProject'])->middleware(['auth:sanctum', 'role:Supervisor']);

/* Route::get('api',function(){

    $role ='Gestor';

    $roles = is_array($role)
            ? $role
            : explode('|', $role);

    $userRole = Auth::user();

    return $userRole;

    foreach ($userRole as $value) {
        if(in_array($value['name'], $roles)){
            return response()->json(['error' => 'rol correcto.'],200);
        }
    }

    return response()->json(['error' => 'No tiene el rol correcto.'],403);



})->middleware(['auth:sanctum']); */
