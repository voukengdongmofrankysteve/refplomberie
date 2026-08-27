<?php

use App\Http\Controllers\Settings\NotificationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    // Notifications par email : l'activation exige un code envoyé à
    // l'adresse, d'où l'étranglement sur l'envoi.
    Route::get('settings/notifications', [NotificationController::class, 'edit'])
        ->name('notifications.edit');
    Route::post('settings/notifications/code', [NotificationController::class, 'sendCode'])
        ->middleware('throttle:6,1')
        ->name('notifications.code');
    Route::post('settings/notifications/confirmer', [NotificationController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('notifications.confirm');
    Route::put('settings/notifications', [NotificationController::class, 'update'])
        ->name('notifications.update');
    Route::delete('settings/notifications', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
