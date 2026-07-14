<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, CausesActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'locale',
        'status',
        'branch_id',
    ];

    protected static function booted(): void
    {
        // Users are NOT branch-scoped for querying (logins are global), but a
        // user created inside a branch context belongs to that branch.
        static::creating(function (self $user) {
            if ($user->branch_id === null) {
                $user->branch_id = \App\Support\BranchContext::current();
            }
        });
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /** Opt-in 2FA is available to everyone; these are the roles it's actively recommended for. */
    public function shouldBeStronglyEncouragedToEnable2fa(): bool
    {
        return $this->hasAnyRole(['owner', 'admin', 'accountant', 'principal']);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function wards(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardian', 'guardian_id', 'student_id')
                    ->withPivot('relation', 'is_primary')
                    ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->hasRole('owner')        => 'owner.dashboard',
            $this->hasRole('admin')        => 'dashboard.admin',
            $this->hasRole('principal')    => 'dashboard.principal',
            $this->hasRole('teacher')      => 'dashboard.teacher',
            $this->hasRole('accountant')   => 'dashboard.accountant',
            $this->hasRole('librarian')    => 'dashboard.librarian',
            $this->hasRole('receptionist') => 'dashboard.receptionist',
            $this->hasRole('student')      => 'dashboard.student',
            $this->hasRole('parent')       => 'dashboard.parent',
            default                        => 'dashboard',
        };
    }
}
