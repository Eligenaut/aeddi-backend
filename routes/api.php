<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\AccueilController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\DataRegisterController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::put('/me', [AuthController::class, 'updateMe'])->middleware('auth:sanctum');
    Route::post('/check-email-allowed', [AuthController::class, 'checkEmailAllowed']);
    Route::post('/create-password', [AuthController::class, 'createPassword']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/check-verification', [AuthController::class, 'checkVerification']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
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


// Tâches
Route::middleware('auth:sanctum')->prefix('taches')->group(function () {
    Route::get('/', [TacheController::class, 'index'])->middleware('permission:show_tache');
    Route::get('/mes-taches', [TacheController::class, 'myTasks'])->middleware('permission:show_tache');
    Route::post('/', [TacheController::class, 'store'])->middleware('permission:create_tache');
    Route::get('/{id}', [TacheController::class, 'show'])->middleware('permission:show_tache');
    Route::put('/{id}', [TacheController::class, 'update'])->middleware('permission:edit_tache');
    Route::delete('/{id}', [TacheController::class, 'destroy'])->middleware('permission:delete_tache');
});

// Data Register (public - for registration forms)
Route::prefix('data-register')->group(function () {
    Route::get('/', [DataRegisterController::class, 'getAll']);
    Route::get('/etablissements', [DataRegisterController::class, 'getEtablissements']);
    Route::get('/promotions', [DataRegisterController::class, 'getPromotions']);
    Route::get('/types-logement', [DataRegisterController::class, 'getTypesLogement']);
    Route::get('/quartiers', [DataRegisterController::class, 'getQuartiers']);
    Route::get('/villes', [DataRegisterController::class, 'getVilles']);
});

// Data Register Admin (CRUD - requires edit_membre or similar permission)
Route::middleware('auth:sanctum')->prefix('admin/data-register')->group(function () {
    // Établissements
    Route::post('/etablissements', [DataRegisterController::class, 'storeEtablissement'])->middleware('permission:create_membre');
    Route::put('/etablissements/{id}', [DataRegisterController::class, 'updateEtablissement'])->middleware('permission:edit_membre');
    Route::delete('/etablissements/{id}', [DataRegisterController::class, 'deleteEtablissement'])->middleware('permission:delete_membre');
    // Parcours
    Route::post('/parcours', [DataRegisterController::class, 'storeParcours'])->middleware('permission:create_membre');
    Route::put('/parcours/{id}', [DataRegisterController::class, 'updateParcours'])->middleware('permission:edit_membre');
    Route::delete('/parcours/{id}', [DataRegisterController::class, 'deleteParcours'])->middleware('permission:delete_membre');
    // Niveaux
    Route::post('/niveaux', [DataRegisterController::class, 'storeNiveau'])->middleware('permission:create_membre');
    Route::put('/niveaux/{id}', [DataRegisterController::class, 'updateNiveau'])->middleware('permission:edit_membre');
    Route::delete('/niveaux/{id}', [DataRegisterController::class, 'deleteNiveau'])->middleware('permission:delete_membre');
    // Promotions
    Route::post('/promotions', [DataRegisterController::class, 'storePromotion'])->middleware('permission:create_membre');
    Route::put('/promotions/{id}', [DataRegisterController::class, 'updatePromotion'])->middleware('permission:edit_membre');
    Route::delete('/promotions/{id}', [DataRegisterController::class, 'deletePromotion'])->middleware('permission:delete_membre');
    // Types de logement
    Route::post('/types-logement', [DataRegisterController::class, 'storeTypeLogement'])->middleware('permission:create_membre');
    Route::put('/types-logement/{id}', [DataRegisterController::class, 'updateTypeLogement'])->middleware('permission:edit_membre');
    Route::delete('/types-logement/{id}', [DataRegisterController::class, 'deleteTypeLogement'])->middleware('permission:delete_membre');
    // Options Campus
    Route::post('/options-campus', [DataRegisterController::class, 'storeOptionCampus'])->middleware('permission:create_membre');
    Route::put('/options-campus/{id}', [DataRegisterController::class, 'updateOptionCampus'])->middleware('permission:edit_membre');
    Route::delete('/options-campus/{id}', [DataRegisterController::class, 'deleteOptionCampus'])->middleware('permission:delete_membre');
    // Sections Campus
    Route::post('/sections-campus', [DataRegisterController::class, 'storeSectionCampus'])->middleware('permission:create_membre');
    Route::put('/sections-campus/{id}', [DataRegisterController::class, 'updateSectionCampus'])->middleware('permission:edit_membre');
    Route::delete('/sections-campus/{id}', [DataRegisterController::class, 'deleteSectionCampus'])->middleware('permission:delete_membre');
    // Blocs Campus
    Route::post('/blocs-campus', [DataRegisterController::class, 'storeBlocCampus'])->middleware('permission:create_membre');
    Route::put('/blocs-campus/{id}', [DataRegisterController::class, 'updateBlocCampus'])->middleware('permission:edit_membre');
    Route::delete('/blocs-campus/{id}', [DataRegisterController::class, 'deleteBlocCampus'])->middleware('permission:delete_membre');
    // Quartiers
    Route::post('/quartiers', [DataRegisterController::class, 'storeQuartier'])->middleware('permission:create_membre');
    Route::put('/quartiers/{id}', [DataRegisterController::class, 'updateQuartier'])->middleware('permission:edit_membre');
    Route::delete('/quartiers/{id}', [DataRegisterController::class, 'deleteQuartier'])->middleware('permission:delete_membre');
    // Villes
    Route::post('/villes', [DataRegisterController::class, 'storeVille'])->middleware('permission:create_membre');
    Route::put('/villes/{id}', [DataRegisterController::class, 'updateVille'])->middleware('permission:edit_membre');
    Route::delete('/villes/{id}', [DataRegisterController::class, 'deleteVille'])->middleware('permission:delete_membre');
});

Route::middleware('auth:sanctum')->get('/dashboard-stats', [MemberController::class, 'dashboardStats']);

// Notifications (polling)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/read-all', [NotificationController::class, 'readAll']);
    Route::post('/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/{id}/unread', [NotificationController::class, 'markUnread']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

// Export
Route::middleware('auth:sanctum')->get('/export/users', [ExportController::class, 'exportUsers'])->middleware('permission:show_membre');
Route::middleware('auth:sanctum')->get('/export/users/xlsx', [ExportController::class, 'exportUsersXlsx'])->middleware('permission:show_membre');

//Permission
Route::middleware('auth:sanctum')->prefix('permissions')->group(function () {
    Route::post('/add', [PermissionController::class, 'addPermission'])->middleware('permission:create_parametre');
    Route::get('/get', [PermissionController::class, 'getRolePermissions'])->middleware('permission:show_parametre');
    Route::post('/reset', [PermissionController::class, 'resetPermissions'])->middleware('permission:edit_parametre');
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
