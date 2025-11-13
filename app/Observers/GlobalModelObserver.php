<?php
// app/Observers/GlobalModelObserver.php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GlobalModelObserver
{
   /**
    * Manejar el evento "created" para cualquier modelo.
    */
   public function created(Model $model): void
   {
      $this->logActivity($model, 'created');
   }

   /**
    * Manejar el evento "updated" para cualquier modelo.
    */
   public function updated(Model $model): void
   {
      $this->logActivity($model, 'updated');
   }

   /**
    * Manejar el evento "deleted" para cualquier modelo.
    */
   public function deleted(Model $model): void
   {
      $this->logActivity($model, 'deleted');
   }

   /**
    * Manejar el evento "restored" para cualquier modelo.
    */
   public function restored(Model $model): void
   {
      $this->logActivity($model, 'restored');
   }

   /**
    * Manejar el evento "forceDeleted" para cualquier modelo.
    */
   public function forceDeleted(Model $model): void
   {
      $this->logActivity($model, 'force_deleted');
   }

   /**
    * Manejar el evento "saved" para cualquier modelo.
    */
   // public function saved(Model $model): void
   // {
   //    $this->logActivity($model, 'saved');
   // }

   /**
    * Registrar actividad en la bitácora
    */
   protected function logActivity(Model $model, string $action): void
   {
      try {
         //code... // Verificar si el modelo tiene la trait Auditable o si queremos loguearlo siempre
         if (!$this->shouldLogActivity($model, $action)) {
            return;
         }

         $oldValues = $action === 'updated' ? $this->getOriginalValues($model) : null;
         $newValues = in_array($action, ['created', 'updated', 'restored']) ?
            $this->getCurrentValues($model) : null;

         ActivityLog::create([
            'action' => $action,
            'description' => $this->getActivityDescription($model, $action),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
         ]);
      } catch (\Throwable $th) {
         Log::error('❌ Error al guardar log: ' . $e->getMessage());
      }
   }

   /**
    * Determinar si se debe loguear la actividad
    */
   protected function shouldLogActivity(Model $model, string $action): bool
   {
      // Opción 1: Solo modelos con trait Auditable
      // return in_array(\App\Traits\Auditable::class, class_uses_recursive($model));

      // Opción 2: Todos los modelos excepto los excluidos
      return !in_array(get_class($model), $this->getExcludedModels());
   }

   /**
    * Modelos excluidos del log
    */
   protected function getExcludedModels(): array
   {
      return [
         ActivityLog::class,
         // Agregar otros modelos que no quieras loguear
         // \App\Models\SomeModel::class,
      ];
   }

   /**
    * Obtener valores originales del modelo
    */
   protected function getOriginalValues(Model $model): array
   {
      $original = $model->getOriginal();
      return $this->filterSensitiveData($original);
   }

   /**
    * Obtener valores actuales del modelo
    */
   protected function getCurrentValues(Model $model): array
   {
      $attributes = $model->getAttributes();
      return $this->filterSensitiveData($attributes);
   }

   /**
    * Filtrar datos sensibles
    */
   protected function filterSensitiveData(array $data): array
   {
      $sensitiveFields = [
         'password',
         'remember_token',
         'api_token',
         'token',
         'secret',
         'access_token',
         'refresh_token',
      ];

      return collect($data)
         ->except($sensitiveFields)
         ->toArray();
   }

   /**
    * Generar descripción de la actividad
    */
   protected function getActivityDescription(Model $model, string $action): string
   {
      $modelName = class_basename($model);
      $modelId = $model->getKey();

      $descriptions = [
         'created' => "Se creó {$modelName} #{$modelId}",
         'updated' => "Se actualizó {$modelName} #{$modelId}",
         'deleted' => "Se eliminó {$modelName} #{$modelId}",
         'restored' => "Se restauró {$modelName} #{$modelId}",
         'force_deleted' => "Se eliminó permanentemente {$modelName} #{$modelId}",
      ];

      return $descriptions[$action] ?? "Acción {$action} en {$modelName} #{$modelId}";
   }
}