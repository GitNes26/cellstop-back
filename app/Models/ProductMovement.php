<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductMovement extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)
    use SoftDeletes;

    protected $table = 'product_movements';

    protected $fillable = [
        'product_id',
        'action',
        'description',
        'origin',
        'destination',
        'executed_by',
        'executed_at',
        'active',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'active' => 'boolean',
    ];

    /* -------------------------------------------------------------
     | 🔗 RELACIONES
     |--------------------------------------------------------------*/

    /**
     * Producto asociado al movimiento
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Usuario que ejecutó la acción
     */
    public function executer()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }




    public function scopeLatestPerProduct(Builder $query, array $filters = [])
    {
        $subQuery = DB::table('product_movements')
            ->selectRaw('product_id, MAX(executed_at) as max_executed_at')
            ->when(isset($filters['productIds']), function ($q) use ($filters) {
                $q->whereIn('product_id', $filters['productIds']);
            })
            ->when(isset($filters['destination']), function ($q) use ($filters) {
                $q->where('destination', $filters['destination']);
            })
            ->when(
                isset($filters['start_date']) && isset($filters['end_date']),
                function ($q) use ($filters) {
                    $q->whereBetween('executed_at', [
                        $filters['start_date'],
                        $filters['end_date'],
                    ]);
                }
            )
            ->groupBy('product_id');

        return $query
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
            ->from('product_movements as pm')
            ->joinSub($subQuery, 'latest', function ($join) {
                $join->on('pm.product_id', '=', 'latest.product_id')
                    ->on('pm.executed_at', '=', 'latest.max_executed_at');
            })
            ->join('products as p', 'p.id', '=', 'pm.product_id')
            ->join('product_types as pt', 'pt.id', '=', 'p.product_type_id')
            ->leftJoin('lote_details as ld', 'ld.product_id', '=', 'p.id')
            ->leftJoin('lotes as l', 'l.id', '=', 'ld.lote_id')
            ->leftJoin('vw_users as s', 's.id', '=', 'l.seller_id')
            ->leftJoin('visits as v', function ($join) {
                $join->whereRaw(
                    "JSON_SEARCH(v.product_ids, 'one', pm.product_id)"
                );
                // $join->whereRaw(
                //     "JSON_CONTAINS(v.product_ids, CAST(pm.product_id AS JSON))"git 
                // );
            })
            ->select([
                'pm.*',
                'p.iccid',
                'p.imei',
                'p.fecha',
                'p.celular',
                'p.folio',
                'p.num_orden',
                'p.tipo_sim',
                'p.modelo',
                'p.marca',
                'p.color',
                'p.location_status',
                'p.activation_status',
                'p.product_type_id',
                'pt.product_type',
                'p.evaluations_rejected',
                'l.lote',
                'l.lada',
                'l.seller_id',
                's.username',
                's.full_name',
                'v.id as visit_id',
                'v.visit_type',
            ]);
    }


    public function scopeApplyFilters(Builder $query, array $filters = [])
    {
        return $query
            ->when(
                isset($filters['start_date']) && isset($filters['end_date']),
                function ($q) use ($filters) {
                    $q->whereBetween('pm.executed_at', [
                        $filters['start_date'],
                        $filters['end_date'],
                    ]);
                }
            )

            ->when(
                isset($filters['seller_id']) && count($filters['seller_id']) > 0,
                function ($q) use ($filters) {
                    $q->whereIn('l.seller_id', $filters['seller_id']);
                }
            )

            ->when(isset($filters['location_status']), function ($q) use ($filters) {
                $q->where('p.location_status', $filters['location_status']);
            })

            ->when(isset($filters['activation_status']), function ($q) use ($filters) {
                $q->where('p.activation_status', $filters['activation_status']);
            })

            ->when(isset($filters['product_type_id']), function ($q) use ($filters) {
                $q->where('p.product_type_id', $filters['product_type_id']);
            })

            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('p.celular', 'like', "%{$filters['search']}%")
                        ->orWhere('p.iccid', 'like', "%{$filters['search']}%")
                        ->orWhere('p.imei', 'like', "%{$filters['search']}%")
                        ->orWhere('p.folio', 'like', "%{$filters['search']}%");
                });
            });
    }
}