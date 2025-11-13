<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)
    use SoftDeletes;

    /**
     * Especificar la conexion si no es la por default
     * @var string
     */
    // protected $connection = 'db_mysql';

    /**
     * Los atributos que se solicitan y se guardan con la funcion fillable() en el controlador.
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'menu',
        'caption',
        'type',
        'belongs_to',
        'url',
        'icon',
        'order',
        'show_counter',
        'counter_name',
        'others_permissions',
        'read_only',
        'active',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'deleted_at',
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'menus';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Obtener los usuarios relacionados a un rol.
     */
    // public function users()
    // {
    //     return $this->hasMany(VW_User::class, 'role_id', 'id'); //primero se declara FK y despues la PK
    // }

    /**
 * Valores defualt para los campos especificados.
 * @var array
 */
    // protected $attributes = [
    //     'active' => true,
    // ];
}