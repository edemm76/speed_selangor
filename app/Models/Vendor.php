<?php

namespace App\Models;

use App\Enums\ApplicantType;
use App\Enums\VendorStatus;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

/**
 * A Pemohon (applicant): an individual or a company.
 * The table is still called `vendors`, as in the thesis ERD.
 */
class Vendor extends Authenticatable
{
    // RoutesNotifications (not Notifiable) lets us email password-reset links
    // without clashing with our own `notifications` table.
    use RoutesNotifications;

    protected $table = 'vendors';
    protected $primaryKey = 'vendor_id';

    protected $fillable = [
        'applicant_type',
        'company_name',
        'registration_no',
        'ic_no',
        'pic_name',
        'email',
        'no_phone',
        'address',
        'password_hash',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed', // plain text is hashed automatically on save
            'status' => VendorStatus::class,
            'applicant_type' => ApplicantType::class,
        ];
    }

    /** Tell Laravel's login system our password column is `password_hash` */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /** Forgot password: e-mail a reset link that opens the applicant reset page */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, 'vendor'));
    }

    public function isActive(): bool
    {
        return $this->status === VendorStatus::Active;
    }

    public function isCompany(): bool
    {
        return $this->applicant_type === ApplicantType::Company;
    }

    /** Name to show in the UI: company name for companies, person's name for individuals */
    public function displayName(): string
    {
        return $this->isCompany() && $this->company_name ? $this->company_name : $this->pic_name;
    }

    /** SSM number for companies, IC number for individuals */
    public function identityNo(): ?string
    {
        return $this->isCompany() ? $this->registration_no : $this->ic_no;
    }

    /* ---------------- Relationships ---------------- */

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'vendor_id', 'vendor_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'vendor_id', 'vendor_id');
    }
}