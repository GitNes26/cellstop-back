<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chip extends Model
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
        'product_id',
        'iccid',
        'imei',
        'phone_number',
        'operator',
        'location_status',
        'activation_status',
        'active'
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'chips';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function device()
    {
        return $this->hasOne(Device::class);
    }

    public function activations()
    {
        return $this->hasMany(Activation::class);
    }

    public function portabilities()
    {
        return $this->hasMany(Portability::class);
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