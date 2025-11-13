<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointOfSale extends Model
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
    protected $fillable = ['name', 'contact_name', 'contact_phone', 'address', 'lat', 'lon', 'ubication', 'active'];


    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'points_of_sale';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';

    public function visits()
    {
        return $this->hasMany(Sale::class, 'pos_id');
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
