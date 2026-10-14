<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    protected $table = 'verifications';
    protected $primaryKey = 'verification_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'verified_by_admin_id',
        'decision',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            // decision is either action_required or verified
            'decision' => ApplicationStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'verified_by_admin_id', 'admin_id');
    }
}