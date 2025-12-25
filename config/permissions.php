<?php

return [
    // Canonical permission slugs grouped by module (for reference)
    'canonical' => [
        'users' => [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.restore'
        ],
        'roles' => [
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.restore', 'roles.manage-priority'
        ],
        'permissions' => [
            'permissions.view', 'permissions.manage'
        ],
        'contracts' => [
            'contract-types.view', 'contract-types.create', 'contract-types.edit', 'contract-types.delete', 'contract-types.restore',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete', 'contracts.restore', 'contracts.export',
            'contracts.versions.view', 'contracts.versions.create', 'contracts.versions.edit', 'contracts.versions.delete', 'contracts.versions.restore',
            'contracts.manage-reminders'
        ],
        'notifications' => [
            'notification-recipients.view', 'notification-recipients.create', 'notification-recipients.edit', 'notification-recipients.delete', 'notification-recipients.restore',
            'notifications.manage'
        ],
        'branches' => [
            'branches.view', 'branches.create', 'branches.update', 'branches.delete', 'branches.restore', 'branches.export'
        ],
    ],

    // Aliases map: old or alternative slugs => canonical slug
    // Fill this with mappings as you standardize names. Empty by default.
    'aliases' => [
        // Example: 'users.index' => 'users.view'
    ],
];
