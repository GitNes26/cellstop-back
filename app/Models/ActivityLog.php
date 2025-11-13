<?php
// app/Models/ActivityLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'action',
        'description',
        'old_values',
        'new_values',
        'extra_data',
        'model_type',
        'model_id',
        'user_id',
        'ip_address',
        'user_agent',
        'url',
        'method'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'extra_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación polimórfica con cualquier modelo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que realizó la acción
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para filtrar por modelo
     */
    public function scopeForModel($query, $model)
    {
        return $query->where('model_type', get_class($model))
            ->where('model_id', $model->id);
    }

    /**
     * Scope para filtrar por tipo de modelo
     */
    public function scopeForModelType($query, $modelClass)
    {
        return $query->where('model_type', $modelClass);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope para los últimos logs
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Obtener cambios formateados
     */
    public function getChangesAttribute()
    {
        if ($this->action !== 'updated') {
            return null;
        }

        $changes = [];
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue
                ];
            }
        }

        return $changes;
    }
}
