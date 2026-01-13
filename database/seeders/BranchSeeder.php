<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $branches = [
            'Mumbai Branch',
            'Delhi Branch',
            'Bangalore Branch',
            'Pune Branch',
            'Chennai Branch',
        ];

        foreach ($branches as $name) {
            Branch::updateOrCreate([
                'name' => $name,
            ], [
                'created_by' => null,
                'updated_by' => null,
            ]);
        }
    }
}
