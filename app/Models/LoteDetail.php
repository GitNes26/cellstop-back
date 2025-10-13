<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoteDetail extends Model
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
        'lote_id',
        'chip_id',
        'assigned_at',
        'assigned_by',
        'active'
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'lote_details';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }
    public function chip()
    {
        return $this->belongsTo(Chip::class, 'chip_id');
    }
    public function assigner()
    {
        return $this->belongsTo(VW_User::class, 'assigned_by');
    }


    protected $casts = [];

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
