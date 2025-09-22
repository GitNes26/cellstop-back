<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                "product_type" => "Chip",
                "description" => "Chip SIM para dispositivos móviles."
            ],
            [
                "product_type" => "Dispositivo",
                "description" => "Dispositivo electrónico con chip integrado."
            ],
        ];

        $data = array_map(function ($position) {
            return [
                'product_type' => $position['product_type'],
                'description' => $position['description'],
                'created_at' => now(),
            ];
        }, $products);

        DB::table('products')->insert($data);
    }
}