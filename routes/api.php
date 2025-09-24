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
    $user = $request->user();
    $user->load(['roles']);
    
    // Obtener todos los permisos del usuario desde sus roles
    $permissions = $user->roles->pluck('permissions')->flatten()->unique()->values();
    
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->roles->first() ? $user->roles->first()->slug : 'viewer',
        'roles' => $user->roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'permissions' => $role->permissions
            ];
        }),
        'permissions' => $permissions,
        'avatar' => $user->avatar,
        'phone' => $user->phone,
        'status' => $user->status,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at
    ]);
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

// Rutas para el módulo de tareas
Route::group(['prefix' => 'tasks', 'middleware' => 'auth:sanctum'], function() {
    // CRUD básico de tareas
    Route::get('/', [\App\Http\Controllers\TaskController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\TaskController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\TaskController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\TaskController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\TaskController::class, 'destroy']);
    
    // Funcionalidades específicas
    Route::get('/gantt/data', [\App\Http\Controllers\TaskController::class, 'ganttData']);
    Route::patch('/{id}/progress', [\App\Http\Controllers\TaskController::class, 'updateProgress']);
    
    // Comentarios de tareas
    Route::get('/{taskId}/comments', [\App\Http\Controllers\TaskCommentController::class, 'index']);
    Route::post('/{taskId}/comments', [\App\Http\Controllers\TaskCommentController::class, 'store']);
    Route::put('/{taskId}/comments/{commentId}', [\App\Http\Controllers\TaskCommentController::class, 'update']);
    Route::delete('/{taskId}/comments/{commentId}', [\App\Http\Controllers\TaskCommentController::class, 'destroy']);
    
    // Archivos adjuntos de tareas
    Route::get('/{taskId}/attachments', [\App\Http\Controllers\TaskAttachmentController::class, 'index']);
    Route::post('/{taskId}/attachments', [\App\Http\Controllers\TaskAttachmentController::class, 'store']);
    Route::get('/{taskId}/attachments/{attachmentId}/download', [\App\Http\Controllers\TaskAttachmentController::class, 'download']);
    Route::put('/{taskId}/attachments/{attachmentId}', [\App\Http\Controllers\TaskAttachmentController::class, 'update']);
    Route::delete('/{taskId}/attachments/{attachmentId}', [\App\Http\Controllers\TaskAttachmentController::class, 'destroy']);
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

Route::group(['prefix' => 'users', 'middleware' => ['auth:sanctum', 'permission:users.manage']], function() {
    Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\UserController::class, 'store']);
    Route::get('/{user}', [\App\Http\Controllers\UserController::class, 'show']);
    Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy']);
    Route::post('/{user}/enable-2fa', [\App\Http\Controllers\UserController::class, 'enable2FA']);
    Route::post('/{user}/disable-2fa', [\App\Http\Controllers\UserController::class, 'disable2FA']);
    Route::post('/password-reset', [\App\Http\Controllers\UserController::class, 'sendPasswordReset']);
});

Route::group(['prefix' => 'roles', 'middleware' => ['auth:sanctum', 'permission:roles.manage']], function() {
    Route::get('/', [\App\Http\Controllers\RoleController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\RoleController::class, 'store']);
    Route::get('/{role}', [\App\Http\Controllers\RoleController::class, 'show']);
    Route::put('/{role}', [\App\Http\Controllers\RoleController::class, 'update']);
    Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy']);
    Route::get('/permissions/list', [\App\Http\Controllers\RoleController::class, 'permissions']);
});

Route::group(['prefix' => 'import', 'middleware' => 'auth:sanctum'], function() {
    Route::post('/projects', [\App\Http\Controllers\ImportController::class, 'uploadProjects']);
    Route::get('/template', [\App\Http\Controllers\ImportController::class, 'downloadTemplate']);
    Route::get('/history', [\App\Http\Controllers\ImportController::class, 'getImportHistory']);
    Route::post('/validate', [\App\Http\Controllers\ImportController::class, 'validateFile']);
});

Route::group(['prefix' => 'dashboard', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/metrics', [\App\Http\Controllers\DashboardController::class, 'getMetrics']);
    Route::get('/projects-status', [\App\Http\Controllers\DashboardController::class, 'getProjectsByStatus']);
    Route::get('/budget-analysis', [\App\Http\Controllers\DashboardController::class, 'getBudgetAnalysis']);
    Route::get('/user-activity', [\App\Http\Controllers\DashboardController::class, 'getUserActivity']);
    Route::get('/export', [\App\Http\Controllers\DashboardController::class, 'exportDashboard']);
});

Route::group(['prefix' => 'reports', 'middleware' => 'auth:sanctum'], function() {
    Route::get('/available', [\App\Http\Controllers\ReportController::class, 'getAvailableReports']);
    Route::get('/project/{project}', [\App\Http\Controllers\ReportController::class, 'generateProjectReport']);
    Route::get('/summary', [\App\Http\Controllers\ReportController::class, 'generateSummaryReport']);
});

Route::group(['prefix' => 'settings', 'middleware' => ['auth:sanctum', 'permission:system.manage']], function() {
    Route::get('/system-info', [\App\Http\Controllers\SettingsController::class, 'getSystemInfo']);
    Route::get('/general', [\App\Http\Controllers\SettingsController::class, 'getGeneralSettings']);
    Route::put('/general', [\App\Http\Controllers\SettingsController::class, 'updateGeneralSettings']);
    Route::get('/security', [\App\Http\Controllers\SettingsController::class, 'getSecuritySettings']);
    Route::put('/security', [\App\Http\Controllers\SettingsController::class, 'updateSecuritySettings']);
    Route::get('/email', [\App\Http\Controllers\SettingsController::class, 'getEmailSettings']);
    Route::put('/email', [\App\Http\Controllers\SettingsController::class, 'updateEmailSettings']);
    Route::post('/email/test', [\App\Http\Controllers\SettingsController::class, 'testEmailConnection']);
    Route::post('/cache/clear', [\App\Http\Controllers\SettingsController::class, 'clearCache']);
    Route::post('/system/optimize', [\App\Http\Controllers\SettingsController::class, 'optimizeSystem']);
    Route::get('/logs', [\App\Http\Controllers\SettingsController::class, 'getSystemLogs']);
    Route::delete('/logs', [\App\Http\Controllers\SettingsController::class, 'clearLogs']);
});
