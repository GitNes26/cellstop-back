<?php
// app/Traits/Auditable.php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
   /**
    * Relación con los logs de actividad
    */
   public function activityLogs(): MorphMany
   {
      return $this->morphMany(ActivityLog::class, 'model');
   }

   /**
    * Obtener logs por acción
    */
   public function getLogsByAction($action)
   {
      return $this->activityLogs()->action($action);
   }

   /**
    * Log manual de actividad
    */
   public function logManualActivity($action, $description = null, $extraData = [])
   {
      return ActivityLog::create([
         'action' => $action,
         'description' => $description,
         'model_type' => get_class($this),
         'model_id' => $this->getKey(),
         'user_id' => auth()->id(),
         'ip_address' => request()->ip(),
         'user_agent' => request()->userAgent(),
         'url' => request()->fullUrl(),
         'method' => request()->method(),
         'extra_data' => $extraData,
      ]);
   }
}
