<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'message',
        'target_type', // vendor | admin
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** Who received this notification */
    public function recipients(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'notification_id', 'notification_id');
    }
}