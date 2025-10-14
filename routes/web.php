<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Role-specific dashboards
Route::get('superadmin/dashboard', [App\Http\Controllers\SuperadminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:superadmin'])
    ->name('superadmin.dashboard');

Route::get('admin/dashboard', [App\Http\Controllers\AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('admin.dashboard');

Route::get('user/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:user'])
    ->name('user.dashboard');

// Legacy dashboard route (will redirect based on role)
Route::get('dashboard', function () {
    $user = auth()->user();
    
    if ($user->hasRole('superadmin')) {
        return redirect()->route('superadmin.dashboard');
    } elseif ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('user')) {
        return redirect()->route('user.dashboard');
    }
    
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Superadmin only routes
Route::middleware(['auth', 'verified', 'role:superadmin'])->group(function () {
    // User Management
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::post('users/{id}/restore', [App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force-delete', [App\Http\Controllers\UserController::class, 'forceDelete'])->name('users.force-delete');
});

// Public contact form routes (no authentication required)
Route::get('contact', [App\Http\Controllers\ContactController::class, 'showForm'])->name('contact.form');
Route::post('contact/bigfm', [App\Http\Controllers\ContactController::class, 'submitBigfm'])->name('contact.bigfm');
Route::post('contact/rpr1', [App\Http\Controllers\ContactController::class, 'submitRpr1'])->name('contact.rpr1');
Route::post('contact/regenbogen', [App\Http\Controllers\ContactController::class, 'submitRegenbogen'])->name('contact.regenbogen');
Route::post('contact/rockfm', [App\Http\Controllers\ContactController::class, 'submitRockfm'])->name('contact.rockfm');
Route::post('contact/bigkarriere', [App\Http\Controllers\ContactController::class, 'submitBigkarriere'])->name('contact.bigkarriere');

// Authenticated contact management routes (all authenticated users can access)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('contact-messages', [App\Http\Controllers\ContactController::class, 'index'])->name('contact.index');
    Route::get('contact-messages/{submission}', [App\Http\Controllers\ContactController::class, 'show'])->name('contact.show');
    Route::post('contact-messages/{submission}/toggle-read', [App\Http\Controllers\ContactController::class, 'toggleRead'])->name('contact.toggle-read');
    Route::delete('contact-messages/{submission}', [App\Http\Controllers\ContactController::class, 'destroy'])->name('contact.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
