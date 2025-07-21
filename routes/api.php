<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Arr;
use App\Http\Controllers\DocumentController;

Route::post('/send-test-email', function (Request $request) {
    $recipient = $request->input('to');

    if (!$recipient) {
        return response()->json(['error' => 'Recipient email is required'], 400);
    }

    // Send test email
    Mail::raw('This is a test email from Laravel using Truehost SMTP', function ($message) use ($recipient) {
        $message->to($recipient)
                ->subject('Test Email from Laravel');
    });

    // Get the active mailer
    $mailer = config('mail.default', 'smtp');

    // Return used config values
    $mailConfig = [
        'mailer' => $mailer,
        'host' => config("mail.mailers.{$mailer}.host"),
        'port' => config("mail.mailers.{$mailer}.port"),
        'username' => config("mail.mailers.{$mailer}.username"),
        'encryption' => config("mail.mailers.{$mailer}.encryption"),
        'from_address' => config('mail.from.address'),
        'from_name' => config('mail.from.name'),
    ];

    return response()->json([
        'message' => 'Test email sent to ' . $recipient,
        'mail_config_used' => $mailConfig,
    ]);
});


Route::post('/login', [AuthController::class, 'login']);
Route::post('/update-password', [AuthController::class, 'updatePassword']);
Route::get('/users-with-passwords', [AuthController::class, 'getAllUsersWithPasswords']);
Route::post('/force-update-password', [AuthController::class, 'forceUpdatePasswordByEmail']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/dms-admin-login', [AuthController::class, 'dmsAdminLogin']);
Route::get('/test-api', function () {
    return response()->json(['message' => 'API is working']);
});


Route::middleware('api.token.auth', 'check.token.expiry')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/create-user', [AuthController::class, 'createUser']);
    
});


// DMS Routes – Protected by API Token
Route::middleware(['api.token.auth'])->prefix('documents')->group(function () {
    
    // Only for SUPER ADMINs
    Route::middleware('can.create-edit-delete-docs')->group(function () {
        Route::post('/', [DocumentController::class, 'store']);
        Route::post('/{id_number}', [DocumentController::class, 'update']);
        Route::delete('/{id_number}', [DocumentController::class, 'destroy']);
    });

    // Only for DMS ADMINs
    Route::middleware('can.view.docs')->group(function () {
      Route::get('/{id_number}', [DocumentController::class, 'show']);
    });

        // Route::get('/{id_number}', [DocumentController::class, 'show']);
});