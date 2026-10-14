<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\PermitType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'application_id';

    protected $fillable = [
        'vendor_id',
        'reference_no',
        'permit_type',
        'project_title',
        'project_description',
        'site_address',
        'latitude',
        'longitude',
        'current_status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'permit_type' => PermitType::class,
            'current_status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /* ---------------- Relationships ---------------- */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vendor_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id', 'application_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id', 'application_id')
            ->orderBy('created_at')
            ->orderBy('status_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'application_id', 'application_id')
            ->latest('created_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'application_id', 'application_id')
            ->latest('generated_at');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'application_id', 'application_id');
    }

    /* ---------------- Scopes ---------------- */

    /** Everything except drafts (what admins should see) */
    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('current_status', '!=', ApplicationStatus::Draft->value);
    }

    /** Still being processed (not draft, not approved/rejected) */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereNotIn('current_status', [
            ApplicationStatus::Draft->value,
            ApplicationStatus::Approved->value,
            ApplicationStatus::Rejected->value,
        ]);
    }

    /** In progress and older than the Speed Selangor target (default 14 days) */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->inProgress()
            ->where('submitted_at', '<', now()->subDays(config('speed.target_days')));
    }

    /* ---------------- Helpers ---------------- */

    /** Next reference number for this year, e.g. SS-2026-0001 */
    public static function generateReferenceNo(): string
    {
        $prefix = config('speed.reference_prefix') . '-' . now()->year . '-';

        $last = static::where('reference_no', 'like', $prefix . '%')
            ->orderByDesc('reference_no')
            ->value('reference_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Change the status AND write a row to application_status_histories.
     * Use this everywhere instead of updating current_status directly.
     * $adminId = null means the vendor made the change.
     */
    public function changeStatus(ApplicationStatus $status, ?string $remarks = null, ?int $adminId = null): ApplicationStatusHistory
    {
        $this->current_status = $status;
        $this->save();

        return $this->statusHistories()->create([
            'status' => $status->value,
            'remarks' => $remarks,
            'updated_by_admin_id' => $adminId,
        ]);
    }

    /** Whole days since submission (0 for drafts) */
    public function daysElapsed(): int
    {
        return $this->submitted_at ? (int) $this->submitted_at->diffInDays(now()) : 0;
    }

    /** Days left before the target (negative = overdue) */
    public function daysRemaining(): int
    {
        return config('speed.target_days') - $this->daysElapsed();
    }

    public function isOverdue(): bool
    {
        return $this->submitted_at !== null
            && ! $this->current_status->isFinal()
            && $this->current_status !== ApplicationStatus::Draft
            && $this->daysElapsed() > config('speed.target_days');
    }
}