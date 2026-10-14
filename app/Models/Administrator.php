<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class Administrator extends Authenticatable
{
    // RoutesNotifications (not Notifiable) lets us email password-reset links
    // without clashing with our own `notifications` table.
    use RoutesNotifications;

    protected $table = 'administrators';
    protected $primaryKey = 'admin_id';

    protected $fillable = [
        'name',
        'email',
        'no_phone',
        'password_hash',
        'role',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed', // plain text is hashed automatically on save
        ];
    }

    /** Tell Laravel's login system our password column is `password_hash` */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /* ---------------- Relationships ---------------- */

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'updated_by_admin_id', 'admin_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'verified_by_admin_id', 'admin_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by_admin_id', 'admin_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'admin_id', 'admin_id');
    }
}