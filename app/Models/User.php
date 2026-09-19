<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens;
        use HasFactory;
        use Notifiable;

        public const ROLE_SUPER_ADMIN = 'super_admin';
        public const ROLE_BRANCH_MANAGER = 'branch_manager';
        public const ROLE_STAFF = 'staff';

        /**
         * The attributes that are mass assignable.
         *
         * @var array<int, string>
         */
        protected $fillable = [
            'name',
            'email',
            'password',
            'role',
            'branch_id',
            'is_active',
            'last_login_at',
        ];

        /**
         * The attributes that should be hidden for serialization.
         *
         * @var array<int, string>
         */
        protected $hidden = [
            'password',
            'remember_token',
        ];

        /**
         * The attributes that should be cast.
         *
         * @var array<string, string>
         */
        protected $casts = [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];

        public function branch(): BelongsTo
        {
            return $this->belongsTo(Branch::class);
        }

        public function notifications(): HasMany
        {
            return $this->hasMany(Notification::class);
        }

        public function auditLogs(): HasMany
        {
            return $this->hasMany(AuditLog::class);
        }

        public function isSuperAdmin(): bool
        {
            return $this->role === self::ROLE_SUPER_ADMIN;
        }

        public function isBranchManager(): bool
        {
            return $this->role === self::ROLE_BRANCH_MANAGER;
        }

        public function isStaff(): bool
        {
            return $this->role === self::ROLE_STAFF;
        }

        public function isManagerOrAbove(): bool
        {
            return $this->isSuperAdmin() || $this->isBranchManager();
        }
}
