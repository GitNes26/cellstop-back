<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Situation extends Model
{
    use HasFactory;

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
        'folio',
        'requester_id', #tabla personal_info
        'beneficiary',
        'beneficiary_age',
        'subcategory_id', #tabla subcategories
        'description',
        'support',
        'status',
        // 'family_data', #esta tabla tendra el id de la situacion
        'family_data_finish',
        // 'living_conditions_data_id',
        'living_conditions_data_finish',
        // 'economic_data_id'
        'economic_data_finish',
        // 'documents_data', #esta tabla tendra el id de la situacion
        'documents_data_finish',
        // 'evidences_data', #esta tabla tendra el id de la situacion
        'evidences_data_finish',
        'finish',
        'img_firm_requester',
        'amount',
        'situation_settings_id', #tabla sutuation_settings

        'current_page',
        'end_date',

        'registered_by', #tabla users
        'authorized_by', #tabla users
        'authorized_comment',
        'authorized_at',
        'follow_up_by', #tabla users
        'follow_up_at',
        'rejected_by', #tabla users 
        'rejected_comment',
        'rejected_at',
        'active',
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'situations';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';

    // Relación con la tabla personal_info (Solicitante)
    public function requester()
    {
        return $this->belongsTo(VW_PersonalInfo::class, 'requester_id');
    }

    // Relación con la tabla subcategories
    public function subcategory()
    {
        return $this->belongsTo(VW_Subcategory::class, 'subcategory_id');
    }

    // Relación con la tabla situation_settings
    public function situationSetting()
    {
        return $this->belongsTo(VW_SituationSetting::class, 'situation_settings_id');
    }

    // Relación con la tabla users (Usuario que registró)
    public function register()
    {
        return $this->belongsTo(VW_User::class, 'registered_by');
    }

    // Relación con la tabla users (Usuario que autorizó)
    public function authorizer()
    {
        return $this->belongsTo(VW_User::class, 'authorized_by');
    }

    // Relación con la tabla users (Usuario que da seguimiento)
    public function followUper()
    {
        return $this->belongsTo(VW_User::class, 'follow_up_by');
    }

    // Relación con la tabla users (Usuario que rechazó)
    public function rejecter()
    {
        return $this->belongsTo(VW_User::class, 'rejected_by');
    }

    // Relaciones con otras tablas con el ID de `situacion`
    public function familyData()
    {
        return $this->hasMany(FamilyData::class, 'situation_id');
    }

    public function livingData()
    {
        return $this->hasOne(LivingConditionsData::class, 'situation_id');
    }

    public function economicData()
    {
        return $this->hasOne(EconomicData::class, 'situation_id');
    }

    public function documentsData()
    {
        return $this->hasMany(DocumentData::class, 'situation_id');
    }

    public function evidencesData()
    {
        return $this->hasMany(EvidenceData::class, 'situation_id');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'situation_id');
    }

    /**
     * Valores defualt para los campos especificados.
     * @var array
     */
    // protected $attributes = [
    //     'active' => true,
    // ];
}