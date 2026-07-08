<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = json_decode(
            file_get_contents(database_path('seeders/data/customers.json')),
            true
        );

        DB::table('customers')->insert($customers);
    }
}
