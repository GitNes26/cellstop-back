<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, Auditable; // Audtiable(logs)

    protected $fillable = [
        'title',
        'message',
        'type',
        'action_url',
        'menu_id',
        'created_by'
    ];

    public function targets()
    {
        return $this->hasMany(NotificationTarget::class);
    }

    public function creator()
    {
        return $this->belongsTo(VW_User::class, 'created_by');
    }
}
