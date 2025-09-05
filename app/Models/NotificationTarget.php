<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'target_type',
        'target_id',
        'seen',
        'seen_at'
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
