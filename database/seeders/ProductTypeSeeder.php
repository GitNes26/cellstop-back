<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product_types = [
            [
                "product_type" => "SIM",
                "description" => "Chip SIM para dispositivos móviles."
            ],
            [
                "product_type" => "Dispositivo",
                "description" => "Dispositivo electrónico con chip integrado."
            ],
            [
                "product_type" => "E-SIM",
                "description" => "Dispositivo electrónico con chip integrado."
            ],
        ];

        $data = array_map(function ($position) {
            return [
                'product_type' => $position['product_type'],
                'description' => $position['description'],
                'created_at' => now(),
            ];
        }, $product_types);

        DB::table('product_types')->insert($data);
    }
}