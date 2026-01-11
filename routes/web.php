<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController; // Added RoleController import

// Landing → login page
Route::get('/', function () {
    return view('auth.login');
})->name('root');

// Show login form (named so `route('login')` resolves)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Handle login submission
Route::post('/login', function (Request $request) {
    // Accept either email or mobile for login
    if ($request->filled('email')) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $attempt = Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->filled('remember'));
        $errorKey = 'email';
    } else {
        $credentials = $request->validate([
            'mobile' => ['required', 'digits:10'],
            'password' => ['required'],
        ]);

        $attempt = Auth::attempt(['mobile' => $credentials['mobile'], 'password' => $credentials['password']], $request->filled('remember'));
        $errorKey = 'mobile';
    }

    if ($attempt) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        $errorKey => 'The provided credentials do not match our records.',
    ])->withInput();
});

// Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Dashboard (requires auth)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Roles management (requires auth)
Route::middleware('auth')->group(function () {
    Route::resource('roles', RoleController::class); // Register roles resource routes

    // Ajax: return count of active mapped users for a role
    Route::get('/roles/{role}/mapped-active-users', [RoleController::class, 'mappedActiveUsers'])
        ->name('roles.mapped_active_users');

    // Role restore
    Route::post('/roles/{id}/restore', [RoleController::class, 'restore'])
        ->name('roles.restore');

    // Role hierarchy routes (allow manage-priority view and update)
    Route::get('/roles/{role}/hierarchy', [RoleController::class, 'managePriority'])
        ->name('roles.hierarchy');
    Route::post('/roles/hierarchy/update', [RoleController::class, 'updatePriority'])
        ->name('roles.hierarchy.update');

    // Role permissions management
    Route::get('/roles/{role}/permissions', [RoleController::class, 'managePermissions'])
        ->name('roles.permissions');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
        ->name('roles.permissions.update');
});

// User management (copied from mshcscontr) — requires auth
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

    // Developer profile routes (self-update without role validation)
    Route::get('/profile', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
});

// Branch management (requires auth)
Route::middleware('auth')->group(function () {
    Route::resource('branches', App\Http\Controllers\BranchController::class);
    Route::get('branches/{branch}/export', [App\Http\Controllers\BranchController::class, 'export'])->name('branches.export');
    Route::post('branches/{branch}/restore', [App\Http\Controllers\BranchController::class, 'restore'])->name('branches.restore');
});

