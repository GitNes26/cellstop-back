<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
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
    protected $fillable = ['product_type', 'description', 'status', 'import_id', 'active'];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'product_types';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';


    public function product()
    {
        return $this->hasOne(Product::class);
    }
    public function fileImport()
    {
        return $this->belongsTo(Import::class);
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