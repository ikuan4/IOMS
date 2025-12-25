<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $names = ['North Branch','South Branch','East Branch','West Branch','Central Branch'];

        foreach ($names as $name) {
            Branch::updateOrCreate([
                'name' => $name,
            ], [
                'created_by' => null,
                'updated_by' => null,
            ]);
        }
    }
}
