<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductMovement extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)
    use SoftDeletes;

    protected $table = 'product_movements';

    protected $fillable = [
        'product_id',
        'action',
        'description',
        'origin',
        'destination',
        'executed_by',
        'executed_at',
        'active',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'active' => 'boolean',
    ];

    /* -------------------------------------------------------------
     | 🔗 RELACIONES
     |--------------------------------------------------------------*/

    /**
     * Producto asociado al movimiento
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Usuario que ejecutó la acción
     */
    public function executer()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
