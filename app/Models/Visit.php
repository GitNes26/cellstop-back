<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
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
        'seller_id',
        'pos_id',
        'product_ids',
        'contact_name',
        'contact_phone',
        'visit_type',
        'lat',
        'lon',
        'ubication',
        'evidence_photo',
        'chips_delivered',
        'chips_sold',
        'chips_remaining',
        'observations',
        'active'
    ];

    protected $casts = [
        'product_ids' => 'array',
        'active' => 'boolean',
    ];
    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'visits';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    public function products()
    {
        return $this->hasMany(Product::class, 'id', 'product_ids');
    }

    public function seller()
    {
        return $this->belongsTo(VW_User::class, 'seller_id');
    }

    public function point_of_sale()
    {
        return $this->belongsTo(PointOfSale::class, 'pos_id');
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