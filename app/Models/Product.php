<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)
    use SoftDeletes;

    /**
     * Especificar la conexion si no es la por default
     * @var string
     */
    //protected $connection = "db_mysql";

    /**
     * Los atributos que se solicitan y se guardan con la funcion fillable() en el controlador.
     * @var array<int, string>
     */
    protected $fillable = [
        'region',
        'celular',
        'iccid',
        'imei',
        'fecha',
        'tramite',
        'estatus',
        'comentario',
        'fza_vta_prepago',
        'fza_vta_padre',
        'usuario',
        'folio',
        'producto',
        'num_orden',
        'estatus_orden',
        'motivo_error',
        'tipo_sim',

        'modelo',
        'marca',
        'color',
        'location_status',
        'activation_status',

        'product_type_id',
        'import_id',
        // 'created_by',
        'active',
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'products';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    /* -------------------------------------------------------------
     | 🔗 RELACIONES
     |--------------------------------------------------------------*/

    /**
     * Tipo de producto al que pertenece este producto
     */
    public function product_type()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * Importación a la que pertenece este producto (carga masiva)
     */
    public function import()
    {
        return $this->belongsTo(Import::class, 'import_id');
    }

    public function uploader()
    {
        return $this->hasOneThrough(
            VW_User::class,     // Modelo destino
            Import::class,      // Modelo intermedio
            'id',               // FK en Import (imports.id)
            'id',               // FK en User (users.id)  
            'import_id',        // FK en Product (products.import_id)
            'uploaded_by'       // FK en Import (imports.uploaded_by)
        );
    }
    /**
     * Accessor para el nombre del usuario que subió
     */
    public function getImportUploaderNameAttribute()
    {
        return $this->import?->uploader?->username ?? 'N/A';
    }

    /**
     * Scope para cargar el importador
     */
    public function scopeWithImportUploader($query)
    {
        return $query->with(['import.uploader']);
    }

    // /**
    //  * Usuario que registró este producto
    //  */
    // public function creator()
    // {
    //     return $this->belongsTo(User::class, 'created_by');
    // }

    /**
     * Movimientos del producto (por ejemplo distribución o venta)
     */
    public function movements()
    {
        return $this->hasMany(ProductMovement::class, 'product_id');
    }

    /**
     * Detalles de distribución de este producto
     */
    // public function distributionDetails()
    // {
    //     return $this->hasMany(ProductDistributionDetail::class, 'product_id');
    // }

    /**
     * Bitácora de acciones relacionadas con el producto
     */
    // public function logs()
    // {
    //     return $this->morphMany(Log::class, 'loggable');
    // }
    // 🔹 Relación con LoteDetail (si el producto pertenece a un lote)
    public function loteDetails()
    {
        return $this->hasMany(LoteDetail::class, 'product_id');
    }

    public function activations()
    {
        return $this->hasMany(Activation::class);
    }

    public function portabilities()
    {
        return $this->hasMany(Portability::class);
    }

    // protected $casts = [
    //     'fecha_activ' => 'date',
    //     'fecha_prim_llam' => 'date',
    //     'fecha_dol' => 'date',
    //     'fecha_evaluacion' => 'date',
    //     'fecha_publicacion' => 'date',
    //     'active' => 'boolean'
    // ];


    
    /* -------------------------------------------------------------
     | 🔍 SCOPES
     |--------------------------------------------------------------*/
    /**
     * Scope para buscar por folio o rango de folios
     */
    public function scopeByFolio(Builder $query, $folio)
    {
        return $query->where('folio', $folio);
    }

    public function scopeByFolioRange(Builder $query, $startFolio, $endFolio = null)
    {
        if ($endFolio) {
            return $query->whereBetween('folio', [$startFolio, $endFolio]);
        }

        return $query->where('folio', '>=', $startFolio);
    }

    public function scopeSearchByFolio(Builder $query, $searchTerm)
    {
        return $query->where('folio', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Scope para filtrar por fechas de creación
     */
    public function scopeCreatedBetween(Builder $query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->whereDate('created_at', $startDate);
    }

    public function scopeCreatedAfter(Builder $query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    public function scopeCreatedBefore(Builder $query, $date)
    {
        return $query->where('created_at', '<=', $date);
    }

    /**
     * Scope para status de ubicación
     */
    public function scopeByLocationStatus(Builder $query, $status)
    {
        return $query->where('location_status', $status);
    }

    public function scopeWhereLocationStatusIn(Builder $query, array $statuses)
    {
        return $query->whereIn('location_status', $statuses);
    }

    public function scopeWhereLocationStatusNot(Builder $query, $status)
    {
        return $query->where('location_status', '!=', $status);
    }

    /**
     * Scope para status de activación
     */
    public function scopeByActivationStatus(Builder $query, $status)
    {
        return $query->where('activation_status', $status);
    }

    public function scopePreActive(Builder $query)
    {
        return $query->where('activation_status', 'Pre-activado');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('activation_status', 'Activado');
    }

    public function scopePortado(Builder $query)
    {
        return $query->where('activation_status', 'Portado');
    }

    public function scopeWhereActivationStatusIn(Builder $query, array $statuses)
    {
        return $query->whereIn('activation_status', $statuses);
    }

    /**
     * Scope para tipo de producto
     */
    public function scopeByProductType(Builder $query, $productTypeId)
    {
        return $query->where('product_type_id', $productTypeId);
    }

    public function scopeWhereProductTypeIn(Builder $query, array $productTypeIds)
    {
        return $query->whereIn('product_type_id', $productTypeIds);
    }

    public function scopeWhereProductTypeNot(Builder $query, $productTypeId)
    {
        return $query->where('product_type_id', '!=', $productTypeId);
    }

    /**
     * Scopes combinados para consultas comunes
     */
    public function scopeActiveInLocation(Builder $query, $locationStatus)
    {
        return $query->active()->byLocationStatus($locationStatus);
    }

    public function scopeRecentProducts(Builder $query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByFolioAndType(Builder $query, $folio, $productTypeId)
    {
        return $query->byFolio($folio)->byProductType($productTypeId);
    }

    public function scopeCreatedByWithStatus(Builder $query, $userId, $activationStatus)
    {
        return $query->byCreator($userId)->byActivationStatus($activationStatus);
    }

    /**
     * Scope para ordenamiento optimizado por índices
     */
    public function scopeOrderByFolio(Builder $query, $direction = 'asc')
    {
        return $query->orderBy('folio', $direction);
    }

    public function scopeOrderByCreation(Builder $query, $direction = 'desc')
    {
        return $query->orderBy('created_at', $direction);
    }

    public function scopeOrderByTypeAndFolio(Builder $query)
    {
        return $query->orderBy('product_type_id')->orderBy('folio');
    }

    /**
     * Scope para reporting y estadísticas
     */
    public function scopeForReporting(Builder $query, $startDate, $endDate)
    {
        return $query->with(['productType', 'creator'])
            ->createdBetween($startDate, $endDate)
            ->orderByCreation();
    }

    /**
     * Scope para búsqueda avanzada usando múltiples índices
     */
    public function scopeAdvancedSearch(Builder $query, array $filters)
    {
        return $query->when(isset($filters['folio']), function ($q) use ($filters) {
            return $q->searchByFolio($filters['folio']);
        })
            ->when(isset($filters['location_status']), function ($q) use ($filters) {
                return $q->byLocationStatus($filters['location_status']);
            })
            ->when(isset($filters['activation_status']), function ($q) use ($filters) {
                return $q->byActivationStatus($filters['activation_status']);
            })
            ->when(isset($filters['product_type_id']), function ($q) use ($filters) {
                return $q->byProductType($filters['product_type_id']);
            })
            ->when(isset($filters['created_by']), function ($q) use ($filters) {
                return $q->byCreator($filters['created_by']);
            })
            ->when(isset($filters['start_date']), function ($q) use ($filters) {
                $endDate = $filters['end_date'] ?? null;
                return $q->createdBetween($filters['start_date'], $endDate);
            });
    }



    /**
     * Valores defualt para los campos especificados.
     * @var array
     */
    // protected $attributes = [
    //     'active' => true,
    // ];

    /**
 * Accesores adicionales para el modelo.
 * @var array
 */
    // protected $appends = ['full_name', 'full_name_reverse'];
}