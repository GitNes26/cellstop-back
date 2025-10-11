<?php

namespace App\Services;

use App\Models\ChipMovement;
use Illuminate\Support\Facades\Auth;

class ChipMovementService
{
   /**
    * Registra un movimiento en la bitácora de chips.
    */
   public static function log($chipId, $action, $description = null, $origin = null, $destination = null)
   {
      try {
         ChipMovement::create([
            'chip_id'     => $chipId,
            'action'      => $action,
            'description' => $description,
            'origin'      => $origin,
            'destination' => $destination,
            'executed_by' => Auth::id(),
            'executed_at' => now(),
            'active'      => true,
         ]);
      } catch (\Exception $e) {
         \Log::error("Error al registrar movimiento de chip: " . $e->getMessage());
      }
   }
}
