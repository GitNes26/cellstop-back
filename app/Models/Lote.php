<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
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
        "lote",
        'seller_id',
        'description',
        'created_by',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];


    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'lotes';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';



    public function seller()
    {
        return $this->belongsTo(VW_User::class, 'seller_id');
    }
    public function creator()
    {
        return $this->belongsTo(VW_User::class, 'created_by');
    }
    public function details()
    {
        return $this->hasMany(LoteDetail::class, 'lote_id');
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