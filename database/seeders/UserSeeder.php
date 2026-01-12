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
                'username' => 'sa',
                'email' => 'sa@gmail.com',
                'password' => Hash::make('sadmin'),
                'role_id' => 1, //SuperAdmin
                'employee_id' => null, //SuperAdmin
            ],
            // [
            //     'email' => 'ruben@gmail.com',
            //     'username' => 'RubenDZ',
            //     'password' => Hash::make('123456'),
            //     'role_id' => 3, //Vendedor
            //     'employee_id' => 1, //Ruben Dedor 
            // ]
            [
                'username' => 'RosarioEV',
                'email' => 'rosario.cellstop@outlook.com',
                'password' => Hash::make('123456'),
                'role_id' => 2, //Vendedor
                'employee_id' => 1, //Rosario 
            ],
            [
                'username' => 'OficinaOF',
                'email' => 'cellstopoficina@gmail.com',
                'password' => Hash::make('123456'),
                'role_id' => 3, //Vendedor
                'employee_id' => 2, //Oficina 
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