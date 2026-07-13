<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SysCounterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan sys_counters punya minimal key yang dipakai oleh AutoNumberService.
        // Error yang muncul: "System counter configuration missing for key: unit"
        // sehingga kita insert counter dengan key = 'unit'.

        DB::table('sys_counters')->updateOrInsert(
            ['key' => 'unit'],
            [
                'prefix' => 'UNT-',
                // format dipakai oleh AutoNumberService: {PREFIX},{YEAR},{YEAR_SHORT},{MONTH},{SEQUENCE}
                // AutoNumberService melakukan replace {PREFIX} dan {SEQUENCE}
                // sehingga format cukup memuat placeholder tersebut.
                'format' => '{PREFIX}{SEQUENCE}',

                'sequence_length' => 5,
                'last_sequence' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
