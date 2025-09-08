<?php

namespace App\Imports;

use App\Models\Chip;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChipImport implements ToModel, WithHeadingRow
{
   protected $importId;

   public function __construct($importId)
   {
      $this->importId = $importId;
   }

   public function model(array $row)
   {
      // Crear producto tipo chip si no existe
      $product = Product::firstOrCreate([
         'product_type' => 'chip',
         'description' => 'Chip automático desde importación',
         'status' => 'activo',
         'active' => true
      ]);

      return new Chip([
         'product_id' => $product->id,
         'iccid' => $row['iccid'] ?? null,
         'imei' => $row['imei'] ?? null,
         'phone_number' => $row['numero'] ?? null,
         'operator' => $row['operador'] ?? null,
         'location_status' => 'stock',
         'activation_status' => 'virgen',
         'active' => true
      ]);
   }
}
