<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
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
        'id',
        'payroll_number',
        'avatar',
        'name',
        'plast_name',
        'mlast_name',
        'cellphone',
        'office_phone',
        'ext',
        'img_firm',
        'ine_front',
        'ine_back',
        'pin_color',
        'position_id',
        'department_id',
        // 'user_id',
        'active'
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'employees';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Obtener el puesto al que pertenece el empleado.
     */
    public function position()
    {   //primero se declara FK y despues la PK del modelo asociado
        // return $this->hasOne(Position::class, 'id');
        return $this->belongsTo(Position::class);
    }

    /**
     * Obtener departamento al que pertenece el empleado.
     */
    public function department()
    {   //primero se declara FK y despues la PK del modelo asociado
        // return $this->hasOne(Department::class, 'id');
        return $this->belongsTo(Department::class);
    }

    public function user()
    {   //primero se declara FK y despues la PK del modelo asociado
        return $this->hasOne(VW_User::class, 'employee_id');
    }

    public function getFullNameAttribute()
    {
        $names = array_filter([
            $this->name,
            $this->plast_name,
            $this->mlast_name
        ]);

        return trim(implode(' ', $names));
    }

    public function getFullNameReverseAttribute()
    {
        $names = array_filter([
            $this->plast_name,
            $this->mlast_name,
            $this->name
        ]);

        return trim(implode(' ', $names));
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
    protected $appends = ['full_name', 'full_name_reverse'];
}
