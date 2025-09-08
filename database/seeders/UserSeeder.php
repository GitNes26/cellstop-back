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
        DB::table('users')->insert([
            'email' => 'sa@gmail.com',
            'username' => 'sa',
            'password' => Hash::make('sadmin'),
            'role_id' => 1, //SuperAdmin
            'employee_id' => null, //SuperAdmin
            'created_at' => now()
        ]);
    }
}
