<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;

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


// User management (copied from mshcscontr) — requires auth
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
});

