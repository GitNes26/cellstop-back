<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
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
        'assignment_id',
        'user_id',
        'pos_id',
        'buyer_name',
        'buyer_phone',
        'sale_date',
        'latitude',
        'longitude',
        'evidence_photo',
        'status',
        'active'
    ];


    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'sales';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function vendor()
    {
        return $this->belongsTo(VW_User::class, 'user_id');
    }

    public function pointOfSale()
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
