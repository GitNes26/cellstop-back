<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                "payroll_number" => "1",
                "avatar" => "employees/1/1-AVATAR.PNG",
                "name" => "RUBEN",
                "plast_name" => "DEDOR",
                "mlast_name" => "ZOTE",
                "cellphone" => "8700000001",
                "office_phone" => NULL,
                "ext" => NULL,
                "img_firm" => NULL,
                "ine_front" => NULL,
                "ine_back" => NULL,
                "position_id" => 2,
                "department_id" => 2,
            ]
        ];

        $data = array_map(function ($employee) {
            return [
                'payroll_number' => $employee['payroll_number'],
                'avatar' => $employee['avatar'],
                'name' => $employee['name'],
                'plast_name' => $employee['plast_name'],
                'mlast_name' => $employee['mlast_name'],
                'cellphone' => $employee['cellphone'],
                'office_phone' => $employee['office_phone'],
                'ext' => $employee['ext'],
                'img_firm' => $employee['img_firm'],
                'ine_front' => $employee['ine_front'],
                'ine_back' => $employee['ine_back'],
                'position_id' => $employee['position_id'],
                'department_id' => $employee['department_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $employees);

        DB::table('employees')->insert($data);
    }
}
