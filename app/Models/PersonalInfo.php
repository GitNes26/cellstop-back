<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInfo extends Model
{
    /**
     * Especificar la conexion si no es la por default
     * @var string
     */
    //protected $connection = "db_mysql";

    /**
     * Los atributos que se solicitan y se guardan con la funcion fillable() en el controlador.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'plast_name',
        'mlast_name',
        'gender',
        'civil_status_id',
        'is_working',
        'email',
        'phone',
        'curp',
        'birthdate',
        'community_id',
        'street',
        'num_ext',
        'num_int',
        // 'img_ine',
        // 'img_photo',
        'section',
        'validity',
        'active',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'personal_info';

    /**
     * Valores defualt para los campos especificados.
     * @var array
     */
    protected $attributes = [
        'active' => true,
    ];
}
