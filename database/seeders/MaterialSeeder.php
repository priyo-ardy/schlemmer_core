<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = json_decode(
            file_get_contents(database_path('seeders/data/material.json')),
            true
        );

        DB::table('materials')->insert($materials);
    }
}
