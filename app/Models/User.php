<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;


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
        'username',
        'email',
        'password',
        'role_id',
        'employee_id',
        'active',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'users';

    /**
     * Obtener rol asociado con el user.
     */
    public function role()
    {   //primero se declara FK y despues la PK del modelo asociado
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Obtener empleado asociado con el user.
     */
    public function employee()
    {   //primero se declara FK y despues la PK del modelo asociado
        return $this->belongsTo(VW_Employee::class, 'employee_id', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Valores defualt para los campos especificados.
     * @var array
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * Los atributos que se deben emitir.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}