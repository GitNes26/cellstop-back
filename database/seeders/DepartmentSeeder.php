<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            "ADMINISTRACION",
            "VENTAS"
        ];

        $data = array_map(function ($department) {
            return [
                'department' => $department,
                'created_at' => now(),
            ];
        }, $departments);

        DB::table('departments')->insert($data);
    }
}
