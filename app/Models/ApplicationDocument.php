<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ApplicationDocument extends Model
{
    protected $table = 'application_documents';
    protected $primaryKey = 'document_id';

    // Only an uploaded_at column, no created_at / updated_at
    const CREATED_AT = 'uploaded_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'document_type',
        'file_name',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    /** Malay label for this document type, e.g. "Salinan Geran Tanah" */
    public function label(): string
    {
        return $this->application?->permit_type?->requiredDocuments()[$this->document_type]
            ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    /** Public URL to open / download the file */
    public function url(): string
    {
        return Storage::disk(config('speed.upload.disk'))->url($this->file_path);
    }
}