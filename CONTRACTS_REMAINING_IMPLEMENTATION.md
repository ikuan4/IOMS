# Contract Management - Remaining Implementation Files

## Created Files Summary

### Models (✓ Completed)
- ContractType.php
- Contract.php
- ContractVersion.php
- StoredFile.php
- ContractVersionFile.php
- ContractReminder.php
- NotificationRecipient.php

### Controllers (✓ Completed)
- ContractTypeController.php
- NotificationRecipientController.php
- ContractController.php

### Exports (✓ Completed)
- ContractExport.php

### Mail (✓ Completed)
- ContractExpiryNotification.php

### Blade Views Created
✓ contract-types/index.blade.php
✓ contract-types/create.blade.php
✓ contract-types/edit.blade.php
✓ notification-recipients/index.blade.php
✓ notification-recipients/create.blade.php
✓ notification-recipients/edit.blade.php
✓ notification-recipients/show.blade.php
✓ contracts/index.blade.php

### Remaining Blade Views (Need Creation)

#### contracts/create.blade.php
- Form with contract_type_id, contract_with, grace_period_days
- Initial version fields: start_date, end_date, description
- File upload (multiple)
- Reminder days (dynamic add/remove)
- Notification recipients (checkboxes)
- Branch-specific filtering for contract types and recipients

#### contracts/edit.blade.php
- Similar to create but for editing basic contract info
- Cannot edit version info (separate version editing)
- Shows current version info as read-only

#### contracts/show.blade.php
- Display contract details
- Show all versions with timeline
- List attached files with download links
- Display reminders
- Display assigned notification recipients
- Action buttons: Edit, Export Excel, Create New Version, Send Test Notification

#### contracts/versions/create.blade.php
- Create new version for existing contract
- Fields: version_number (auto), start_date, end_date, description
- File upload (multiple)
- Note: Version number automatically incremented

#### contracts/versions/edit.blade.php
- Edit existing version
- Can only edit latest version
- Fields: start_date, end_date, description
- File management (add/remove files)

#### emails/contract-expiry.blade.php
- Email template for contract expiry notifications
- Display contract number, type, contract_with, dates, days remaining
- IOMS branding with logo and colors

## Routes to Add (web.php)

```php
// Contract Types
Route::middleware(['auth'])->group(function () {
    Route::resource('contract-types', ContractTypeController::class);
    Route::post('contract-types/{id}/restore', [ContractTypeController::class, 'restore'])
        ->name('contract-types.restore');
    
    // Notification Recipients
    Route::resource('notification-recipients', NotificationRecipientController::class);
    Route::post('notification-recipients/{id}/restore', [NotificationRecipientController::class, 'restore'])
        ->name('notification-recipients.restore');
    
    // Contracts
    Route::resource('contracts', ContractController::class);
    Route::post('contracts/{id}/restore', [ContractController::class, 'restore'])
        ->name('contracts.restore');
    Route::get('contracts/{contract}/export', [ContractController::class, 'exportExcel'])
        ->name('contracts.export');
    Route::post('contracts/{contract}/test-notification', [ContractController::class, 'sendTestNotification'])
        ->name('contracts.test-notification');
    
    // Contract Versions
    Route::get('contracts/{contract}/versions/create', [ContractController::class, 'createVersion'])
        ->name('contracts.versions.create');
    Route::post('contracts/{contract}/versions', [ContractController::class, 'storeVersion'])
        ->name('contracts.versions.store');
    Route::get('contracts/{contract}/versions/{version}/edit', [ContractController::class, 'editVersion'])
        ->name('contracts.versions.edit');
    Route::put('contracts/{contract}/versions/{version}', [ContractController::class, 'updateVersion'])
        ->name('contracts.versions.update');
});
```

## Sidebar Navigation Update

Add to resources/views/partials/sidebar.blade.php:

```blade
{{-- Contract Management --}}
@if(auth()->user() && (
    auth()->user()->can('contract-types.view') ||
    auth()->user()->can('contracts.view') ||
    auth()->user()->can('notification-recipients.view')
))
    <li class="sidebar-item">
        <a href="#" class="sidebar-link has-submenu">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Contract Management</span>
            <svg class="chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </a>
        <ul class="sidebar-submenu">
            @can('contract-types.view')
                <li><a href="{{ route('contract-types.index') }}">Contract Types</a></li>
            @endcan
            @can('contracts.view')
                <li><a href="{{ route('contracts.index') }}">Contracts</a></li>
            @endcan
            @can('notification-recipients.view')
                <li><a href="{{ route('notification-recipients.index') }}">Notification Recipients</a></li>
            @endcan
        </ul>
    </li>
@endif
```

## Next Steps

1. Create remaining blade views (contracts create/edit/show, versions create/edit)
2. Create email template
3. Add routes to web.php
4. Update sidebar navigation
5. Test the complete implementation
