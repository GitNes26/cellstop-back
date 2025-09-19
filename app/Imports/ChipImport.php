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

         'filtro'          => $row['filtro'] ?? null,
         'telefono'        => $row['telefono'] ?? null,
         'imei'            => $row['imei'] ?? null,
         'iccid'           => $row['iccid'] ?? null,
         'estatus_lin'     => $row['estatus_lin'] ?? null,
         'movimiento'      => $row['movimiento'] ?? null,
         'fecha_activ'     => $row['fecha_activ'] ?? null,
         'fecha_prim_llam' => $row['fecha_prim_llam'] ?? null,
         'fecha_dol'       => $row['fecha_dol'] ?? null,
         'estatus_pago'    => $row['estatus_pago'] ?? null,
         'motivo_estatus'  => $row['motivo_estatus'] ?? null,
         'monto_com'       => $row['monto_com'] ?? null,
         'tipo_comision'   => $row['tipo_comision'] ?? null,
         'evaluacion'      => $row['evaluacion'] ?? null,
         'fza_vta_pago'    => $row['fza_vta_pago'] ?? null,
         'fecha_evaluacion' => $row['fecha_evaluacion'] ?? null,
         'folio_factura'   => $row['folio_factura'] ?? null,
         'fecha_publicacion' => $row['fecha_publicacion'] ?? null,
         'active'          => true,

         'location_status' => 'stock',
         'activation_status' => 'virgen',
         'active' => true
      ]);
   }
}
