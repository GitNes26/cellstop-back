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
            [
                "letters" => "ADMON",
                "department" => "ADMINISTRACION"
            ],
            [
                "letters" => "VE",
                "department" => "VENTAS"
            ]
        ];

        $data = array_map(function ($department) {
            return [
                'letters' => $department['letters'],
                'department' => $department['department'],
                'created_at' => now(),
            ];
        }, $departments);

        DB::table('departments')->insert($data);
    }
}
