<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $canonical = config('permissions.canonical', []);
        $slugs = array_values(array_unique(array_merge(
            $canonical['tickets'] ?? [],
            $canonical['ticket-types'] ?? [],
            $canonical['ticket-modules'] ?? []
        )));

        if (empty($slugs)) {
            $slugs = [
                'tickets.view', 'tickets.pending.view', 'tickets.create', 'tickets.edit', 'tickets.delete', 'tickets.restore',
                'ticket-types.view', 'ticket-types.create', 'ticket-types.edit', 'ticket-types.delete', 'ticket-types.restore',
                'ticket-modules.view', 'ticket-modules.create', 'ticket-modules.edit', 'ticket-modules.delete', 'ticket-modules.restore',
            ];
        }

        $now = now();

        foreach ($slugs as $slug) {
            $data = [];

            if (Schema::hasColumn('permissions', 'name')) {
                $data['name'] = Str::title(str_replace(['.', '-'], ' ', $slug));
            }

            if (Schema::hasColumn('permissions', 'slug')) {
                $data['slug'] = $slug;
            }

            if (Schema::hasColumn('permissions', 'group')) {
                if (str_starts_with($slug, 'ticket-types.')) {
                    $data['group'] = 'ticket-types';
                } elseif (str_starts_with($slug, 'ticket-modules.')) {
                    $data['group'] = 'ticket-modules';
                } else {
                    $data['group'] = 'tickets';
                }
            }

            if (Schema::hasColumn('permissions', 'guard_name')) {
                $data['guard_name'] = 'web';
            }

            if (Schema::hasColumn('permissions', 'updated_at')) {
                $data['updated_at'] = $now;
            }

            if (Schema::hasColumn('permissions', 'created_at')) {
                $data['created_at'] = $now;
            }

            // Prefer unique slug when available; fall back to unique (name, guard_name)
            if (Schema::hasColumn('permissions', 'slug')) {
                DB::table('permissions')->updateOrInsert(['slug' => $slug], $data);
            } else {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $data['name'] ?? $slug, 'guard_name' => $data['guard_name'] ?? 'web'],
                    $data
                );
            }
        }

        // Optionally grant new permissions to Developer role (non-destructive)
        $developerRoleId = DB::table('roles')->where('name', 'Developer')->value('id');
        if ($developerRoleId) {
            $permIds = Permission::query()->whereIn('slug', $slugs)->pluck('id')->all();
            foreach ($permIds as $pid) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $developerRoleId, 'permission_id' => $pid],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
