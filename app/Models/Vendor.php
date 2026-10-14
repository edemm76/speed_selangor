<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class Vendor extends Authenticatable
{
    // RoutesNotifications (not Notifiable) lets us email password-reset links
    // without clashing with our own `notifications` table.
    use RoutesNotifications;

    protected $table = 'vendors';
    protected $primaryKey = 'vendor_id';

    protected $fillable = [
        'company_name',
        'registration_no',
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
        ];
    }

    /** Tell Laravel's login system our password column is `password_hash` */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function isActive(): bool
    {
        return $this->status === VendorStatus::Active;
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