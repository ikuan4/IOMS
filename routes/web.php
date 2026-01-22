<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController; // Added RoleController import
use App\Http\Controllers\TicketModuleController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\AuditLogController;

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

    // Ajax: check delete dependencies for role
    Route::get('/roles/{role}/check-delete-dependencies', [RoleController::class, 'checkDeleteDependencies'])
        ->name('roles.check_delete_dependencies');

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

    // Ajax: check user dependencies for restore/activation
    Route::get('/users/{user}/check-dependencies', [UserController::class, 'checkDependencies'])
        ->name('users.check_dependencies');

    // Developer profile routes (self-update without role validation)
    Route::get('/profile', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
});

// Branch management (requires auth)
Route::middleware('auth')->group(function () {
    // Developer-only export: all users with role + branch + audit details
    Route::get('branches/export-system-users', [App\Http\Controllers\BranchController::class, 'exportSystemUsersExcel'])
        ->name('branches.export_system_users');

    Route::resource('branches', App\Http\Controllers\BranchController::class);
    Route::get('branches/{branch}/export', [App\Http\Controllers\BranchController::class, 'export'])->name('branches.export');
    Route::post('branches/{branch}/restore', [App\Http\Controllers\BranchController::class, 'restore'])->name('branches.restore');

    // Ajax: check branch delete dependencies
    Route::get('/branches/{branch}/check-delete-dependencies', [App\Http\Controllers\BranchController::class, 'checkDeleteDependencies'])
        ->name('branches.check_delete_dependencies');
});

// Contract Management (requires auth)
Route::middleware('auth')->group(function () {
    // Contract Types
    Route::resource('contract-types', App\Http\Controllers\ContractTypeController::class);
    Route::get('contract-types/{contractType}/check-delete-dependencies', [App\Http\Controllers\ContractTypeController::class, 'checkDeleteDependencies'])
        ->name('contract-types.check_delete_dependencies');
    Route::post('contract-types/{id}/restore', [App\Http\Controllers\ContractTypeController::class, 'restore'])
        ->name('contract-types.restore');

    // Notification Recipients
    Route::resource('notification-recipients', App\Http\Controllers\NotificationRecipientController::class);
    Route::post('notification-recipients/{id}/restore', [App\Http\Controllers\NotificationRecipientController::class, 'restore'])
        ->name('notification-recipients.restore');

    // Contracts
    Route::resource('contracts', App\Http\Controllers\ContractController::class);
    Route::post('contracts/{id}/restore', [App\Http\Controllers\ContractController::class, 'restore'])
        ->name('contracts.restore');
    Route::get('contracts/{contract}/export', [App\Http\Controllers\ContractController::class, 'exportExcel'])
        ->name('contracts.export');
    Route::post('contracts/{contract}/send-test-notification', [App\Http\Controllers\ContractController::class, 'sendTestNotification'])
        ->name('contracts.send-test-notification');

    // Contract Versions
    Route::get('contracts/{contract}/versions/create', [App\Http\Controllers\ContractVersionController::class, 'create'])
        ->name('contracts.versions.create');
    Route::post('contracts/{contract}/versions', [App\Http\Controllers\ContractVersionController::class, 'store'])
        ->name('contracts.versions.store');
    Route::get('contracts/versions/{version}/edit', [App\Http\Controllers\ContractVersionController::class, 'edit'])
        ->name('contracts.versions.edit');
    Route::put('contracts/versions/{version}', [App\Http\Controllers\ContractVersionController::class, 'update'])
        ->name('contracts.versions.update');
    Route::delete('contracts/versions/{version}', [App\Http\Controllers\ContractVersionController::class, 'destroy'])
        ->name('contracts.versions.destroy');
    Route::post('contracts/versions/{id}/restore', [App\Http\Controllers\ContractVersionController::class, 'restore'])
        ->name('contracts.versions.restore');

    // Delete version file
    Route::delete('contracts/{contract}/versions/{version}/files/{file}', [App\Http\Controllers\ContractVersionController::class, 'deleteFile'])
        ->name('contracts.versions.files.destroy');
});

// Audit Logs (requires auth; super-admin enforced in controller)
Route::middleware('auth')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

// Ticket Management (requires auth)
Route::middleware('auth')->group(function () {
    // Ticket Modules
    Route::resource('ticket-modules', TicketModuleController::class)->except(['show']);
    Route::get('ticket-modules/{ticketModule}/check-delete-dependencies', [TicketModuleController::class, 'checkDeleteDependencies'])
        ->name('ticket-modules.check_delete_dependencies');
    Route::post('ticket-modules/{id}/restore', [TicketModuleController::class, 'restore'])
        ->name('ticket-modules.restore');

    // Ticket Types
    Route::resource('ticket-types', TicketTypeController::class)->except(['show']);
    Route::get('ticket-types/{ticketType}/check-delete-dependencies', [TicketTypeController::class, 'checkDeleteDependencies'])
        ->name('ticket-types.check_delete_dependencies');
    Route::post('ticket-types/{id}/restore', [TicketTypeController::class, 'restore'])
        ->name('ticket-types.restore');

    // Tickets
    Route::get('tickets/pending', [TicketController::class, 'pending'])
        ->name('tickets.pending');

    // Ticket attachments (uploads + secure file access)
    Route::post('tickets/uploads/draft', [TicketAttachmentController::class, 'uploadDraft'])
        ->name('tickets.uploads.draft');

    Route::post('tickets/uploads/draft/delete', [TicketAttachmentController::class, 'deleteDraft'])
        ->name('tickets.uploads.draft-delete');

    Route::post('tickets/{ticket}/uploads', [TicketAttachmentController::class, 'uploadTicket'])
        ->whereNumber('ticket')
        ->name('tickets.uploads.ticket');

    Route::post('tickets/{ticket}/comments/uploads/draft', [TicketAttachmentController::class, 'uploadCommentDraft'])
        ->whereNumber('ticket')
        ->name('tickets.uploads.comment-draft');

    Route::post('tickets/{ticket}/comments/uploads/draft/delete', [TicketAttachmentController::class, 'deleteCommentDraft'])
        ->whereNumber('ticket')
        ->name('tickets.uploads.comment-draft-delete');

    Route::get('tickets/{ticket}/files/{storedFile}/inline', [TicketAttachmentController::class, 'inline'])
        ->whereNumber('ticket')
        ->whereNumber('storedFile')
        ->name('tickets.files.inline');

    Route::get('tickets/{ticket}/files/{storedFile}/download', [TicketAttachmentController::class, 'download'])
        ->whereNumber('ticket')
        ->whereNumber('storedFile')
        ->name('tickets.files.download');

    Route::get('tickets/{ticket}', [TicketController::class, 'show'])
        ->whereNumber('ticket')
        ->name('tickets.show');

    Route::post('tickets/{ticket}/forward', [TicketController::class, 'forward'])
        ->whereNumber('ticket')
        ->name('tickets.forward');

    Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])
        ->whereNumber('ticket')
        ->name('tickets.comments.store');

    Route::resource('tickets', TicketController::class)->except(['show']);
    Route::post('tickets/{id}/restore', [TicketController::class, 'restore'])
        ->name('tickets.restore');
});


