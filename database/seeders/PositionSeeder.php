<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            "Director",
            "Encargado",
            "Recepcionista",
        ];

        $data = array_map(function ($position) {
            return [
                'position' => $position,
                'created_at' => now(),
            ];
        }, $positions);

        DB::table('positions')->insert($data);
    }
}