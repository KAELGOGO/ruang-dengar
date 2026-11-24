<?php

use App\Http\Controllers\Admin\MetricsController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EscalationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Listener\ProfileController;
use App\Http\Controllers\Listener\SlotController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');
Route::view('/bantuan-darurat', 'bantuan-darurat')->name('krisis');

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Module 2: booking & matching (user side)
    Route::middleware('role:user')->group(function () {
        Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    });

    // Sessions: both participants (access checked by CounselingSessionPolicy)
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('sessions.cancel');

    // Module 3: chat (JSON polling, no WebSocket server needed)
    Route::get('/sessions/{session}/chat', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/sessions/{session}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/sessions/{session}/messages', [ChatController::class, 'store'])->name('chat.store');
    Route::post('/sessions/{session}/end', [ChatController::class, 'end'])->name('chat.end');

    // Module 4: escalation & rating
    Route::post('/sessions/{session}/escalate', [EscalationController::class, 'store'])->name('escalations.store');
    Route::get('/sessions/{session}/rating', [RatingController::class, 'create'])->name('ratings.create');
    Route::post('/sessions/{session}/rating', [RatingController::class, 'store'])->name('ratings.store');
    Route::middleware('can:handle-escalations')->group(function () {
        Route::get('/escalations', [EscalationController::class, 'index'])->name('escalations.index');
        Route::post('/escalations/{escalation}/take', [EscalationController::class, 'take'])->name('escalations.take');
        Route::post('/escalations/{escalation}/resolve', [EscalationController::class, 'resolve'])->name('escalations.resolve');
    });

    // Module 1 + 2: listener area
    Route::middleware('role:listener')->prefix('listener')->name('listener.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/documents', [ProfileController::class, 'upload'])->name('documents.store');
        Route::get('/slots', [SlotController::class, 'index'])->name('slots.index');
        Route::post('/slots', [SlotController::class, 'store'])->name('slots.store');
        Route::delete('/slots/{slot}', [SlotController::class, 'destroy'])->name('slots.destroy');
    });

    // Module 1 + 4: admin area
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::get('/documents/{document}', [VerificationController::class, 'document'])->name('documents.show');
        Route::post('/verifications/{profile}/approve', [VerificationController::class, 'approve'])->name('verifications.approve');
        Route::post('/verifications/{profile}/reject', [VerificationController::class, 'reject'])->name('verifications.reject');
        Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics');
    });
});
