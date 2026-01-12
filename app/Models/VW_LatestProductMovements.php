<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class VW_LatestProductMovements extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'vw_latest_product_movements';


    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    // Ejemplo de scope para aplicar filtros dinámicos 
    public function scopeApplyFilters($query, array $filters)
    {
        return $query->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('executed_at', [$filters['start_date'], $filters['end_date']]);
        })->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
            $q->whereIn('seller_id', $filters['seller_id']);
        })->when(isset($filters['folio']), function ($q) use ($filters) {
            $q->where('folio', $filters['folio']);
        })->when(isset($filters['start_date_pre_activation']) && isset($filters['end_date_pre_activation']), function ($q) use ($filters) {
            $q->whereBetween('fecha', [$filters['start_date_pre_activation'], $filters['end_date_']]);
        })
            ->when(isset($filters['import_name']), function ($q) use ($filters) {
                $q->where('import_name', $filters['import_name']);
            })

            ->when(isset($filters['location_status']), function ($q) use ($filters) {
                $q->where('location_status', $filters['location_status']);
            })->when(isset($filters['activation_status']), function ($q) use ($filters) {
                $q->where('activation_status', $filters['activation_status']);
            })->when(isset($filters['product_type_id']), function ($q) use ($filters) {
                $q->where('product_type_id', $filters['product_type_id']);
            })->when(isset($filters['start_date_in_system']) && isset($filters['end_date_in_system']), function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['start_date_'], $filters['end_date_in_system']]);
            })->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('celular', 'like', "%{$filters['search']}%")->orWhere('iccid', 'like', "%{$filters['search']}%")->orWhere('imei', 'like', "%{$filters['search']}%")->orWhere('modelo', 'like', "%{$filters['search']}%")->orWhere('marca', 'like', "%{$filters['search']}%");
                });
            });
    }
}
