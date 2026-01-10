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
                'page_index' => '/app/tablero',
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
                'page_index' => '/app/tablero',
                'created_at' => now(),
            ],
            [
                'role' => 'Vendedor', #3
                'description' => 'Rol dedicado para usuarios que realizan las ventas.',
                'read' => "13,14,15,16,17,18,19,20,21,22,23,24,25",
                'create' => "16,25",
                'update' => null,
                'delete' => null,
                'more_permissions' => '',
                'page_index' => '/app',
                'created_at' => now(),
            ],
        ]);
    }
}