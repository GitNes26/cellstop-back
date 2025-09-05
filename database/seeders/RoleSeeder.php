<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'role' => 'SuperAdmin', #1
                'description' => 'Rol dedicado para la completa configuracion del sistema desde el area de desarrollo.',
                'read' => 'todas',
                'create' => 'todas',
                'update' => 'todas',
                'delete' => 'todas',
                'more_permissions' => 'todas',
                'page_index' => '/app',
                'created_at' => now(),
            ],
            [
                'role' => 'Administrador', #2
                'description' => 'Rol dedicado para usuarios que gestionaran el sistema.',
                'read' => 'todas',
                'create' => 'todas',
                'update' => 'todas',
                'delete' => 'todas',
                'more_permissions' => 'todas',
                'page_index' => '/app',
                'created_at' => now(),
            ],
            // [
            //     'role' => 'Director', #3
            //     'description' => 'Rol dedicado para el director del DIF.',
            //     'read' => '1,2,4,7,8,9,12,13,14,15,16,17,19,20,21,22,23,24',
            //     'create' => '2,7,8,9,13,14,15,17,20,21,22,23,24',
            //     'update' => '2,7,8,9,13,14,15,17,20,21,22,23,24',
            //     'delete' => '2,7,8,9,13,14,15,17,20,21,22,23,24',
            //     'more_permissions' => '',
            //     'page_index' => '/app',
            //     'created_at' => now(),
            // ],
            // [
            //     'role' => 'Encargado', #4
            //     'description' => 'Rol dedicado para personal encargado del departamento.',
            //     'read' => '1,2,4,7,8,9,12,13,14,16,17,19',
            //     'create' => '2,8,9,13,14,17',
            //     'update' => '2,8,9,13,14,17',
            //     'delete' => '2,8,17',
            //     'more_permissions' => '',
            //     'page_index' => '/app',
            //     'created_at' => now(),
            // ],
            // [
            //     'role' => 'Recepcionista', #5
            //     'description' => 'Rol dedicado para personal encargado del departamento.',
            //     'read' => '1,2,4,7,8,9,12,13,14,15,16,17,19,20',
            //     'create' => '2,7,8,9,13,14,15,17,20',
            //     'update' => '2,13,14,15',
            //     'delete' => '2',
            //     'more_permissions' => '',
            //     'page_index' => '/app',
            //     'created_at' => now(),
            // ],
            // [
            //     'role' => 'Ciudadano', #6
            //     'description' => 'Rol dedicado para el ciudadano.',
            //     'read' => '',
            //     'create' => '',
            //     'update' => '',
            //     'delete' => '',
            //     'more_permissions' => '',
            //     'page_index' => '/app',
            //     'created_at' => now(),
            // ]
        ]);
    }
}