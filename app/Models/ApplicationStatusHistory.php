<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_histories';
    protected $primaryKey = 'status_id';

    // Only a created_at column
    const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'updated_by_admin_id',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    /** Null when the vendor made the change */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'updated_by_admin_id', 'admin_id');
    }

    /** "Pegawai: Ahmad" or "Pemohon" for the timeline */
    public function actorName(): string
    {
        return $this->admin ? 'Pegawai: ' . $this->admin->name : 'Pemohon';
    }
}