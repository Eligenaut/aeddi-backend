<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\AccueilController;
use App\Http\Controllers\PermissionController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::post('/check-email-allowed', [AuthController::class, 'checkEmailAllowed']);
    Route::post('/create-password', [AuthController::class, 'createPassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Membres
Route::middleware('auth:sanctum')->prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->middleware('permission:show_membre');
    Route::get('/stats', [MemberController::class, 'stats'])->middleware('permission:show_membre');
    Route::get('/cotisation-stats', [MemberController::class, 'cotisationStats'])->middleware('permission:show_membre');
    Route::get('/{id}', [MemberController::class, 'show'])->middleware('permission:show_membre');
    Route::put('/{id}', [MemberController::class, 'update'])->middleware('permission:edit_membre');
    Route::delete('/{id}', [MemberController::class, 'destroy'])->middleware('permission:delete_membre');
});

// Cotisations
Route::middleware('auth:sanctum')->prefix('cotisations')->group(function () {
    Route::get('/', [CotisationController::class, 'index'])->middleware('permission:show_cotisation');
    Route::post('/', [CotisationController::class, 'store'])->middleware('permission:create_cotisation');
    Route::get('/member', [CotisationController::class, 'getMyCotisations'])->middleware('permission:show_cotisation');
    Route::get('/member/{userId}', [CotisationController::class, 'getMemberCotisations'])->middleware('permission:show_cotisation');
    Route::get('/{id}', [CotisationController::class, 'show'])->middleware('permission:show_cotisation');
    Route::put('/{id}', [CotisationController::class, 'update'])->middleware('permission:edit_cotisation');
    Route::delete('/{id}', [CotisationController::class, 'destroy'])->middleware('permission:delete_cotisation');
    Route::put('/{cotisationId}/member/{userId}/status', [CotisationController::class, 'updateMemberStatus'])->middleware('permission:edit_cotisation');
    Route::delete('/{cotisationId}/member/{userId}', [CotisationController::class, 'deleteMemberCotisation'])->middleware('permission:delete_cotisation');
});

// Activités
Route::middleware('auth:sanctum')->prefix('activites')->group(function () {
    Route::get('/', [ActiviteController::class, 'index'])->middleware('permission:show_activite');
    Route::get('/latest', [ActiviteController::class, 'latest'])->middleware('permission:show_activite');
    Route::post('/', [ActiviteController::class, 'store'])->middleware('permission:create_activite');
    Route::get('/{id}', [ActiviteController::class, 'show'])->middleware('permission:show_activite');
    Route::put('/{id}', [ActiviteController::class, 'update'])->middleware('permission:edit_activite');
    Route::delete('/{id}', [ActiviteController::class, 'destroy'])->middleware('permission:delete_activite');
    Route::delete('/{id}/galerie/{index}', [ActiviteController::class, 'deleteGalerieImage'])->middleware('permission:edit_activite');
});


Route::middleware('auth:sanctum')->get('/dashboard-stats', [MemberController::class, 'dashboardStats']);

// Export
Route::middleware('auth:sanctum')->get('/export/users', [ExportController::class, 'exportUsers']);
Route::middleware('auth:sanctum')->get('/export/users/xlsx', [ExportController::class, 'exportUsersXlsx']);

//Permission
Route::prefix('permissions')->group(function () {
    Route::post('/add', [PermissionController::class, 'addPermission']);
    Route::get('/get', [PermissionController::class, 'getRolePermissions']);
    Route::post('/reset', [PermissionController::class, 'resetPermissions']);
});

Route::prefix('accueil')->group(function () {
    Route::get('/activites', [AccueilController::class, 'activites']);
    Route::get('/bureau',    [AccueilController::class, 'bureau']);
});
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/add-authorized-email', [AuthController::class, 'addAuthorizedEmail'])
        ->middleware('permission:create_membre');
    Route::get('/authorized-emails', [AuthController::class, 'getAuthorizedEmails'])
        ->middleware('permission:show_membre');
    Route::delete('/delete-authorized-email/{id}', [AuthController::class, 'deleteAuthorizedEmail'])
        ->middleware('permission:delete_membre');
});

Route::get('/api/test', function () {
    return ['status' => 'OK'];
});

Route::post('auth/google/mobile', [AuthController::class, 'googleMobile']);
