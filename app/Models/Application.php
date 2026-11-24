<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\PermitType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /** Committee meeting decision (recorded after the application is verified) */
    public function meetingDecision(): HasOne
    {
        return $this->hasOne(MeetingDecision::class, 'application_id', 'application_id');
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

    /**
     * Under review again because the applicant resubmitted after "Tindakan Diperlukan":
     * the latest status history row was made by the applicant (no officer ID).
     */
    public function scopeResubmitted(Builder $query): Builder
    {
        return $query->where('current_status', ApplicationStatus::UnderReview->value)
            ->whereRaw('(select h.updated_by_admin_id from application_status_histories h
                         where h.application_id = applications.application_id
                         order by h.created_at desc, h.status_id desc limit 1) is null');
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
     * It also sends the notifications (see App\Services\Notifier).
     */
    public function changeStatus(ApplicationStatus $status, ?string $remarks = null, ?int $adminId = null): ApplicationStatusHistory
    {
        $this->current_status = $status;
        $this->save();

        $history = $this->statusHistories()->create([
            'status' => $status->value,
            'remarks' => $remarks,
            'updated_by_admin_id' => $adminId,
        ]);

        // [SSMS-UC-500] every status change automatically notifies the other party
        \App\Services\Notifier::statusChanged($this, $status, $remarks, $adminId);

        return $history;
    }

    /**
     * When the final decision (approved / rejected) was made, or null if not decided yet.
     * Uses the status history, so it also works for applications from the seeder.
     */
    public function decidedAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->current_status->isFinal()) {
            return null;
        }

        return $this->statusHistories
            ->where('status', $this->current_status)
            ->last()?->created_at;
    }

    /** Whole days since submission (0 for drafts). Stops counting once a decision is made. */
    public function daysElapsed(): int
    {
        if (! $this->submitted_at) {
            return 0;
        }

        return (int) $this->submitted_at->diffInDays($this->decidedAt() ?? now());
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

    /** When the officer last asked for more information (null if never) */
    public function actionRequestedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->verifications
            ->firstWhere('decision', ApplicationStatus::ActionRequired)?->created_at;
    }

    /** True when the applicant has resubmitted and the officer has not acted yet */
    public function isResubmitted(): bool
    {
        $latest = $this->statusHistories->last();

        return $this->current_status === ApplicationStatus::UnderReview
            && $latest !== null
            && $latest->updated_by_admin_id === null
            && $latest->status === ApplicationStatus::UnderReview;
    }

    /** True when this document was uploaded again after the officer's last request */
    public function isDocumentUpdated(ApplicationDocument $document): bool
    {
        $requestedAt = $this->actionRequestedAt();

        return $requestedAt !== null && $document->uploaded_at?->gt($requestedAt);
    }

    /** The uploaded document of one type (e.g. 'geran_tanah'), or null */
    public function documentFor(string $type): ?ApplicationDocument
    {
        return $this->documents->firstWhere('document_type', $type);
    }

    /**
     * Required documents (for this permit type) that are not uploaded yet.
     * Returns ['geran_tanah' => 'Salinan Geran Tanah', ...]. Empty = complete.
     */
    public function missingDocuments(): array
    {
        $uploaded = $this->documents->pluck('document_type')->all();

        return array_diff_key($this->permit_type->requiredDocuments(), array_flip($uploaded));
    }
}