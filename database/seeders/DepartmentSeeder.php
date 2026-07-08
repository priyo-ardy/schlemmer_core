<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'code' => 'DPT-000001',
                'name' => "GM Office",
                'short_name' => "GM Office",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000002',
                'name' => "Finance And Accounting",
                'short_name' => "FA",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000003',
                'name' => "Business and Sales",
                'short_name' => "BNS",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000004',
                'name' => "Research and Development",
                'short_name' => "R&D",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000005',
                'name' => "Human Resource and General Affair",
                'short_name' => "HRGA",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000006',
                'name' => "Quality",
                'short_name' => "Quality",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000007',
                'name' => "Manufacture",
                'short_name' => "MFG",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000008',
                'name' => "Warehouse and Logistic",
                'short_name' => "WH",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000009',
                'name' => "Quality Management System",
                'short_name' => "QMS",
                'remark' => ''
            ],
            [
                'code' => 'DPT-000010',
                'name' => "Purchasing",
                'short_name' => "Purchasing",
                'remark' => ''
            ],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->insert([
                'uuid' => (string) Str::uuid7(),
                'code' => $dept['code'],
                'name' => $dept['name'],
                'short_name' => $dept['short_name'],
                'remark' => $dept['remark'],
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
