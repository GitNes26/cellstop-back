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
            "ALBERGUE JORNALERO",
            "ASILO DE ANCIANOS",
            "ASISTENCIA SOCIAL",
            "CENTRO DE PSICOTERAPIA FAMILIAR",
            "CLUB DE NIÑOS Y NIÑAS",
            "COMUNICACION SOCIAL",
            "COORDINACION JURIDICA GENERAL",
            "DENTAL",
            "DESARROLLO COMUNITARIO",
            "DIRECCION GENERAL",
            "ESTANCIA INFANTIL FELIPE ANGELES",
            "ESTANCIA INFANTIL TIERRA BLANCA",
            "ESTANCIA INFANTIL VILLA NAPOLES",
            "GRUPOS VULNERABLES",
            "INTERNADO FCO ZARCO",
            "PAMAR",
            "PROCURADURIA",
            "PROGRAMA 3A EDAD",
            "SERVICIOS GENERALES",
            "TRABAJO SOCIAL",
            "VALORES"
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