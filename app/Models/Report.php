<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'report_id';

    // Only a generated_at column
    const CREATED_AT = 'generated_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'generated_by_admin_id',
        'report_type',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'generated_by_admin_id', 'admin_id');
    }
}