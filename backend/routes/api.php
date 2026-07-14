<?php

use App\Http\Controllers\AdminConfigController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Full surface documented in shared/api-contract.md. Same URLs and response
// shapes as the Express prototype so the frontend doesn't care which stack
// serves them.

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// --- Auth (public, rate-limited) -----------------------------------------
Route::post('/auth/register/send-otp', [AuthController::class, 'registerSendOtp'])->middleware('throttle:otp-send');
Route::post('/auth/register/verify-otp', [AuthController::class, 'registerVerifyOtp'])->middleware('throttle:otp-verify');
Route::post('/auth/register/profile', [AuthController::class, 'registerProfile']);
Route::post('/auth/register/pin', [AuthController::class, 'registerPin']);
Route::post('/auth/login/send-otp', [AuthController::class, 'loginSendOtp'])->middleware('throttle:login');
Route::post('/auth/login/verify-otp', [AuthController::class, 'loginVerifyOtp'])->middleware('throttle:otp-verify');
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// --- Public, no auth ------------------------------------------------------
Route::get('/applications/event-types', [ApplicationController::class, 'eventTypes']);
Route::get('/tracking/{trackingId}', [TrackingController::class, 'show']);
// PRD 7.2 step 5: provider webhook, authenticated by signature (TODO) not by
// a citizen session.
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

// --- Citizen (Sanctum token with 'citizen' ability) -----------------------
Route::middleware(['auth:sanctum', 'citizen'])->group(function () {
    Route::get('/citizens/me', [CitizenController::class, 'me']);
    Route::patch('/citizens/me', [CitizenController::class, 'update']);
    Route::get('/citizens/me/dashboard', [CitizenController::class, 'dashboard']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::patch('/applications/{id}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{id}', [ApplicationController::class, 'destroy']);
    Route::post('/applications/{id}/documents', [ApplicationController::class, 'uploadDocument']);
    Route::get('/applications/{id}/documents/{documentId}', [ApplicationController::class, 'downloadDocument']);
    Route::post('/applications/{id}/submit', [ApplicationController::class, 'submit']);
    Route::post('/applications/{id}/resubmit', [ApplicationController::class, 'resubmit']);

    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/mock-confirm', [PaymentController::class, 'mockConfirm']);
    Route::get('/payments/{applicationId}/receipt', [PaymentController::class, 'receipt']);
});

// --- Staff ----------------------------------------------------------------
Route::post('/staff/login', [StaffController::class, 'login'])->middleware('throttle:staff-login');

Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::get('/staff/queue', [StaffController::class, 'queue']);
    Route::get('/staff/applications/{id}', [StaffController::class, 'show']);
    Route::get('/staff/applications/{id}/documents/{documentId}', [StaffController::class, 'downloadDocument']);
    Route::post('/staff/applications/{id}/claim', [StaffController::class, 'claim']);

    Route::middleware('staff:REGISTRATION_OFFICER,SUPERVISOR,ADMIN')->group(function () {
        Route::post('/staff/applications/{id}/request-corrections', [StaffController::class, 'requestCorrections']);
        Route::post('/staff/applications/{id}/approve', [StaffController::class, 'approve']);
        Route::post('/staff/applications/{id}/complete', [StaffController::class, 'complete']);
        Route::post('/staff/applications/{id}/reject', [StaffController::class, 'reject']);
    });

    Route::middleware('staff:ADMIN')->group(function () {
        Route::get('/staff/admin/event-types', [AdminConfigController::class, 'index']);
        Route::patch('/staff/admin/event-types/{eventType}', [AdminConfigController::class, 'update']);
    });
});
