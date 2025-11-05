<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
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
    // protected $fillable = [
    //     'product_id',

    //     'filtro',
    //     'telefono',
    //     'imei',
    //     'iccid',
    //     'estatus_lin',
    //     'movimiento',
    //     'fecha_activ',
    //     'fecha_prim_llam',
    //     'fecha_dol',
    //     'estatus_pago',
    //     'motivo_estatus',
    //     'monto_com',
    //     'tipo_comision',
    //     'evaluacion',
    //     'fza_vta_pago',
    //     'fecha_evaluacion',
    //     'folio_factura',
    //     'fecha_publicacion',
    //     'location_status',
    //     'activation_status',

    //     'import_id',
    //     'active'
    // ];

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
