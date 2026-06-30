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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class);
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
