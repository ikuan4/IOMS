<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;

class CanonicalPermissionsSeeder extends Seeder
{
    public function run()
    {
        $canonical = config('permissions.canonical', []);
        $aliases = config('permissions.aliases', []);

        // Create canonical permission records
        foreach ($canonical as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => Str::title(str_replace(['.', '-'], [' ', ' '], $slug)), 'guard_name' => 'web']
                );
            }
        }

        // Map existing role permissions to canonical permissions
        foreach (Role::with('permissions')->get() as $role) {
            $existing = $role->permissions->pluck('slug')->unique()->toArray();

            foreach ($existing as $es) {
                // Resolve alias or find in canonical lists
                $target = $aliases[$es] ?? $this->findCanonical($es, $canonical) ?? $es;

                $perm = Permission::firstOrCreate(
                    ['slug' => $target],
                    ['name' => Str::title(str_replace(['.', '-'], [' ', ' '], $target)), 'guard_name' => 'web']
                );

                if (!$role->permissions()->where('slug', $perm->slug)->exists()) {
                    $role->permissions()->attach($perm->id);
                }

                // Optionally detach the old permission record if different slug exists as record
                // (We avoid destructive changes here — operator can clean up later.)
            }
        }
    }

    protected function findCanonical(string $slug, array $canonical)
    {
        foreach ($canonical as $group => $list) {
            if (in_array($slug, $list, true)) {
                return $slug;
            }
        }

        return null;
    }
}
