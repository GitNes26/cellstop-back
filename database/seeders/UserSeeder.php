<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'sa@gmail.com',
                'username' => 'sa',
                'password' => Hash::make('sadmin'),
                'role_id' => 1, //SuperAdmin
                'employee_id' => null, //SuperAdmin
            ],
            [
                'email' => 'ruben@gmail.com',
                'username' => 'RubenDZ',
                'password' => Hash::make('123456'),
                'role_id' => 3, //Vendedor
                'employee_id' => 1, //Ruben Dedor 
            ]
        ];

        $data = array_map(function ($user) {
            return [
                'email' => $user['email'],
                'username' => $user['username'],
                'password' => $user['password'],
                'role_id' => $user['role_id'],
                'employee_id' => $user['employee_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $users);

        DB::table('users')->insert($data);
    }
}