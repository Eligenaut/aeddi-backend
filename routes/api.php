<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\AuthController as AuthAuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\ActiviteController;
use App\Http\Controllers\RecoveryCodeController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\ExportController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthAuthController::class, 'login']);
    Route::post('/logout', [AuthAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthAuthController::class, 'user'])->middleware('auth:sanctum');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/create-password', [AuthController::class, 'createPassword']);
    Route::post('/check-email-allowed', [RecoveryCodeController::class, 'checkEmailAllowed']);
    Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/test-profile-image', [AuthController::class, 'testProfileImage'])->middleware('auth:sanctum');
    Route::get('/me', [ProfileController::class, 'me'])->middleware('auth:sanctum');
    Route::put('/me', [ProfileController::class, 'update'])->middleware('auth:sanctum');
});

// Membres
Route::middleware('auth:sanctum')->prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::get('/stats', [MemberController::class, 'stats']);
    Route::get('/cotisation-stats', [MemberController::class, 'cotisationStats']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::put('/{id}', [MemberController::class, 'update']);
    Route::delete('/{id}', [MemberController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->get('/dashboard-stats', [MemberController::class, 'dashboardStats']);

// Export
Route::middleware('auth:sanctum')->get('/export/users', [ExportController::class, 'exportUsers']);
Route::middleware('auth:sanctum')->get('/export/users/xlsx', [ExportController::class, 'exportUsersXlsx']);

// Cotisations
Route::middleware('auth:sanctum')->prefix('cotisations')->group(function () {
    Route::get('/', [CotisationController::class, 'index']);
    Route::post('/', [CotisationController::class, 'store']);
    Route::get('/member', [CotisationController::class, 'getMyCotisations']);
    Route::get('/member/{userId}', [CotisationController::class, 'getMemberCotisations']);
    Route::get('/{id}', [CotisationController::class, 'show']);
    Route::put('/{id}', [CotisationController::class, 'update']);
    Route::delete('/{id}', [CotisationController::class, 'destroy']);
    Route::put('/{cotisationId}/member/{userId}/status', [CotisationController::class, 'updateMemberStatus']);
    Route::delete('/{cotisationId}/member/{userId}', [CotisationController::class, 'deleteMemberCotisation']);
});

// Activités
Route::middleware('auth:sanctum')->prefix('activites')->group(function () {
    Route::get('/', [ActiviteController::class, 'index']);
    Route::post('/', [ActiviteController::class, 'store']);
    Route::get('/{id}', [ActiviteController::class, 'show']);
    Route::put('/{id}', [ActiviteController::class, 'update']);
    Route::delete('/{id}', [ActiviteController::class, 'destroy']);
    Route::put('/{activiteId}/member/{userId}/participation', [ActiviteController::class, 'updateMemberParticipation']);
});

// Administration
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/add-authorized-email', [RecoveryCodeController::class, 'addAuthorizedEmail']);
    Route::get('/authorized-emails', [RecoveryCodeController::class, 'getAuthorizedEmails']);
    Route::delete('/delete-authorized-email/{id}', [RecoveryCodeController::class, 'deleteAuthorizedEmail']);
    Route::post('/clean-expired-codes', [RecoveryCodeController::class, 'cleanExpiredCodes']);
});
Route::get('/api/test', function() {
    return ['status' => 'OK'];
});
