<?php

namespace App\Services;

use App\Models\ProductMovement;
use Illuminate\Support\Facades\Auth;

class ProductMovementService
{
   /**
    * Registra un movimiento en la bitácora de productos.
    */
   public static function log($productId, $action, $description = null, $origin = null, $destination = null, $executedAt = null)
   {
      try {
         ProductMovement::create([
            'product_id'     => $productId,
            'action'      => $action,
            'description' => $description,
            'origin'      => $origin,
            'destination' => $destination,
            'executed_by' => Auth::id(),
            'executed_at' => $executedAt ?? now(),
            'active'      => true,
         ]);
      } catch (\Exception $e) {
         \Log::error("Error al registrar movimiento de producto: " . $e->getMessage());
      }
   }
}