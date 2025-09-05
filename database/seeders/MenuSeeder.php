<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Helper para insertar grupos y sus hijos con id automático
        function insertMenuGroup($group, $order)
        {
            $groupData = [
                'menu' => $group['menu'],
                'caption' => $group['caption'] ?? '',
                'type' => 'group',
                'belongs_to' => 0,
                'icon' => $group['icon'] ?? '',
                'order' => $order,
                'created_at' => now(),
            ];
            $groupId = DB::table('menus')->insertGetId($groupData);
            $itemOrder = 1;
            foreach ($group['items'] as $item) {
                $data = [
                    'menu' => $item['menu'],
                    'type' => 'item',
                    'belongs_to' => $groupId,
                    'url' => $item['url'],
                    'icon' => $item['icon'] ?? '',
                    'order' => $itemOrder,
                    'created_at' => now(),
                ];
                if (isset($item['caption'])) $data['caption'] = $item['caption'];
                if (isset($item['others_permissions'])) $data['others_permissions'] = $item['others_permissions'];
                DB::table('menus')->insert($data);
                $itemOrder++;
            }
        }

        $menuGroups = [
            [
                'menu' => 'Principal',
                'icon' => 'GradeRounded',
                'caption' => '',
                'items' => [
                    [
                        'menu' => 'Tablero',
                        'url' => '/app',
                        'icon' => 'DashboardRounded',
                    ],
                    [
                        'menu' => 'Noticias',
                        'url' => '/app/noticias',
                        'icon' => 'NewspaperRounded',
                    ],
                ],
            ],
            [
                'menu' => 'Configuraciones',
                'icon' => 'SettingsSuggestRounded',
                'caption' => 'Control del sistema, usuarios y roles',
                'items' => [
                    [
                        'menu' => 'Menus',
                        'url' => '/app/configuraciones/menus',
                        'icon' => 'MenuBookRounded',
                    ],
                    [
                        'menu' => 'Roles y Permisos',
                        'url' => '/app/configuraciones/roles-y-permisos',
                        'icon' => '',
                        'others_permissions' => 'Asignar Permisos',
                    ],
                    [
                        'menu' => 'Departamentos',
                        'url' => '/app/configuraciones/departamentos',
                        'icon' => 'ApartmentRounded',
                    ],
                    [
                        'menu' => 'Puestos de trabajo',
                        'url' => '/app/configuraciones/puestos',
                        'icon' => 'WorkspacesRounded',
                    ],
                    [
                        'menu' => 'Empleados',
                        'url' => '/app/configuraciones/empleados',
                        'icon' => 'BadgeRounded',
                    ],
                    [
                        'menu' => 'Usuarios',
                        'url' => '/app/configuraciones/usuarios',
                        'icon' => 'PeopleAltRounded',
                    ],
                    [
                        'menu' => 'Ajustes',
                        'url' => '/app/configuraciones/ajustes',
                        'icon' => 'TuneRounded',
                    ],
                ],
            ],
            [
                'menu' => 'Catalogos',
                'icon' => 'BookmarksRounded',
                'caption' => 'Gestión de Catalogos',
                'items' => [
                    // [
                    //     'menu' => 'Categorias',
                    //     'url' => '/app/catalogos/categorias',
                    //     'icon' => 'IconBuildingSkyscraper',
                    // ],
                ],
            ],
        ];

        $groupOrder = 1;
        foreach ($menuGroups as $group) {
            insertMenuGroup($group, $groupOrder);
            $groupOrder++;
        }
    }
}