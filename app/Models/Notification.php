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

    /** Bootstrap icon + colour for the list, based on the title */
    public function icon(): array
    {
        return match ($this->title) {
            'Permohonan Baharu' => ['bi-file-earmark-plus', 'primary'],
            'Permohonan Berjaya Dihantar' => ['bi-send-check', 'success'],
            'Permohonan Dihantar Semula' => ['bi-arrow-repeat', 'warning'],
            'Permohonan Sedang Disemak' => ['bi-search', 'info'],
            'Tindakan Diperlukan' => ['bi-exclamation-triangle', 'danger'],
            'Permohonan Disahkan' => ['bi-patch-check', 'primary'],
            'Permohonan Diluluskan' => ['bi-award', 'success'],
            'Permohonan Ditolak' => ['bi-x-circle', 'dark'],
            'Melebihi Tempoh Sasaran' => ['bi-alarm', 'danger'],
            default => ['bi-bell', 'secondary'],
        };
    }

    /** Who received this notification */
    public function recipients(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'notification_id', 'notification_id');
    }
}