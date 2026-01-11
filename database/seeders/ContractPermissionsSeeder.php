<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class ContractPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All contract-related permissions
        $permissions = [
            // Contract Types
            [
                'name' => 'View Contract Types',
                'slug' => 'contract-types.view',
                'description' => 'View contract types list',
            ],
            [
                'name' => 'Create Contract Types',
                'slug' => 'contract-types.create',
                'description' => 'Create new contract types',
            ],
            [
                'name' => 'Edit Contract Types',
                'slug' => 'contract-types.edit',
                'description' => 'Edit existing contract types',
            ],
            [
                'name' => 'Delete Contract Types',
                'slug' => 'contract-types.delete',
                'description' => 'Delete contract types',
            ],
            [
                'name' => 'Restore Contract Types',
                'slug' => 'contract-types.restore',
                'description' => 'Restore deleted contract types',
            ],

            // Contracts
            [
                'name' => 'View Contracts',
                'slug' => 'contracts.view',
                'description' => 'View contracts list',
            ],
            [
                'name' => 'Create Contracts',
                'slug' => 'contracts.create',
                'description' => 'Create new contracts',
            ],
            [
                'name' => 'Edit Contracts',
                'slug' => 'contracts.edit',
                'description' => 'Edit existing contracts',
            ],
            [
                'name' => 'Delete Contracts',
                'slug' => 'contracts.delete',
                'description' => 'Delete contracts',
            ],
            [
                'name' => 'Restore Contracts',
                'slug' => 'contracts.restore',
                'description' => 'Restore deleted contracts',
            ],
            [
                'name' => 'Export Contracts',
                'slug' => 'contracts.export',
                'description' => 'Export contracts to Excel',
            ],

            // Contract Versions
            [
                'name' => 'View Contract Versions',
                'slug' => 'contracts.versions.view',
                'description' => 'View contract version details',
            ],
            [
                'name' => 'Create Contract Versions',
                'slug' => 'contracts.versions.create',
                'description' => 'Create new contract versions',
            ],
            [
                'name' => 'Edit Contract Versions',
                'slug' => 'contracts.versions.edit',
                'description' => 'Edit contract versions',
            ],
            [
                'name' => 'Delete Contract Versions',
                'slug' => 'contracts.versions.delete',
                'description' => 'Delete contract versions',
            ],

            // Notification Recipients
            [
                'name' => 'View Notification Recipients',
                'slug' => 'notification-recipients.view',
                'description' => 'View notification recipients list',
            ],
            [
                'name' => 'Create Notification Recipients',
                'slug' => 'notification-recipients.create',
                'description' => 'Create new notification recipients',
            ],
            [
                'name' => 'Edit Notification Recipients',
                'slug' => 'notification-recipients.edit',
                'description' => 'Edit existing notification recipients',
            ],
            [
                'name' => 'Delete Notification Recipients',
                'slug' => 'notification-recipients.delete',
                'description' => 'Delete notification recipients',
            ],
            [
                'name' => 'Restore Notification Recipients',
                'slug' => 'notification-recipients.restore',
                'description' => 'Restore deleted notification recipients',
            ],
        ];

        $now = now();

        foreach ($permissions as $permission) {
            // Check if permission already exists
            $exists = Permission::where('slug', $permission['slug'])->exists();

            if (!$exists) {
                Permission::create(array_merge($permission, [
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        $this->command->info('Contract permissions seeded successfully!');
    }
}
