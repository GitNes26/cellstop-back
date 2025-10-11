<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChipMovement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'chip_movements';

    protected $fillable = [
        'chip_id',
        'action',
        'description',
        'origin',
        'destination',
        'executed_by',
        'executed_at',
        'active',
    ];

    // Relaciones
    public function chip()
    {
        return $this->belongsTo(Chip::class, 'chip_id');
    }

    public function executer()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
