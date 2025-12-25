<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Str;

class DummyRolesSeeder extends Seeder
{
    public function run()
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            $this->call(BranchSeeder::class);
            $branches = Branch::all();
        }

        // Ensure Developer role exists
        $special = [
            ['name' => 'Developer', 'slug' => 'developer', 'is_active' => 1],
        ];

        foreach ($special as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['guard_name' => 'web']));
        }

        // Create 20 dummy roles
        for ($i = 1; $i <= 20; $i++) {
            $name = 'Role ' . $i;
            $slug = Str::slug($name);
            $branch = $branches->random();

            Role::updateOrCreate([
                'slug' => $slug,
            ], [
                'name' => $name,
                'description' => 'Auto-generated role ' . $i,
                'is_active' => 1,
                'guard_name' => 'web',
                'branch_id' => $branch->id,
            ]);
        }
    }
}
