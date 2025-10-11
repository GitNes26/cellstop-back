<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Import extends Model
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
    // protected $fillable = ['file_name', 'file_type', 'uploaded_by', 'active'];
    protected $fillable = [
        'name',
        'type',
        'size',
        'last_modified',
        'path',
        'notes',
        'uploaded_by',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_modified' => 'integer',
    ];


    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'imports';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';



    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chips()
    {
        return $this->hasMany(Chip::class);
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
