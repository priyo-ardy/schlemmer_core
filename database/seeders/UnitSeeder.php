<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = json_decode(
            file_get_contents(database_path('seeders/data/units.json')),
            true
        );

        DB::table('units')->insert($units);
    }
}
