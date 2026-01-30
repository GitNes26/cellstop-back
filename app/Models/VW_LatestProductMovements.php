<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        // Log::info($query);
        // Log::info("filtros" . json_encode($filters, true));

        return $query
            ->when(isset($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('executed_at', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('executed_at', '<=', $filters['end_date']);
            })

            ->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
                if (is_array($filters['seller_id'])) { #=== 'array') {
                    $q->whereIn('seller_id', $filters['seller_id']);
                } else {
                    $q->where('seller_id', $filters['seller_id']);
                }
            })

            ->when(isset($filters['id']), function ($q) use ($filters) {
                if (is_array($filters['id'])) { #=== 'array') {
                    $q->whereIn('id', $filters['id']);
                } else {
                    $q->where('id', $filters['id']);
                }
            })

            ->when(isset($filters['folio']), function ($q) use ($filters) {
                if (is_array($filters['folio'])) { #=== 'array') {
                    $q->whereIn('folio', $filters['folio']);
                } else {
                    $q->where('folio', $filters['folio']);
                }
            })

            ->when(isset($filters['start_date_pre_activation']), function ($q) use ($filters) {
                $q->whereDate('fecha', '>=', $filters['start_date_pre_activation']);
                // ->whereBetween('fecha', [$filters['start_date_pre_activation'], $filters['end_date_pre_activation']]);
            })
            ->when(isset($filters['end_date_pre_activation']), function ($q) use ($filters) {
                $q->whereDate('fecha', '<=', $filters['end_date_pre_activation']);
                // ->whereBetween('fecha', [$filters['start_date_pre_activation'], $filters['end_date_pre_activation']]);
            })

            ->when(isset($filters['import_name']), function ($q) use ($filters) {
                $q->where('import_name', $filters['import_name']);
            })

            ->when(isset($filters['location_status']), function ($q) use ($filters) {
                if (is_array($filters['location_status'])) { #=== 'array') {
                    $q->whereIn('location_status', $filters['location_status']);
                } else {
                    $q->where('location_status', $filters['location_status']);
                }
            })

            ->when(isset($filters['activation_status']), function ($q) use ($filters) {
                if (is_array($filters['activation_status'])) { #=== 'array') {
                    $q->whereIn('activation_status', $filters['activation_status']);
                } else {
                    $q->where('activation_status', $filters['activation_status']);
                }
            })

            ->when(isset($filters['product_type_id']), function ($q) use ($filters) {
                $q->where('product_type_id', $filters['product_type_id']);
            })

            ->when(isset($filters['start_date_in_system']), function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['start_date_in_system']);
                // ->whereBetween('created_at', [$filters['start_date_in_system'], $filters['end_date_in_system']]);
            })
            ->when(isset($filters['end_date_in_system']), function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['end_date_in_system']);
                // ->whereBetween('created_at', [$filters['start_date_in_system'], $filters['end_date_in_system']]);
            })

            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('celular', 'like', "%{$filters['search']}%")->orWhere('iccid', 'like', "%{$filters['search']}%")->orWhere('imei', 'like', "%{$filters['search']}%")->orWhere('modelo', 'like', "%{$filters['search']}%")->orWhere('marca', 'like', "%{$filters['search']}%");
                });
            });
    }
}
