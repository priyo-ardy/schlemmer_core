<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unit_categories = json_decode(
            file_get_contents(database_path('seeders/data/unit_category.json')),
            true
        );

        DB::table('unit_categories')->insert($unit_categories);
    }
}
