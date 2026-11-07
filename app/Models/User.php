<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyStudentEmail);
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('email')) $user->email_verified_at = null;
        });
    }

    public const ROLES = ['admin', 'staff', 'student'];
    public const STATUSES = ['active', 'inactive'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar_path',
    ];

    /**
     * In-memory defaults so freshly-built instances (factories, Auth::login
     * after User::create without explicit status) behave like DB rows.
     */
    protected $attributes = [
        'role' => 'student',
        'status' => 'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isStaff(): bool { return $this->role === 'staff'; }
    public function isStudent(): bool { return $this->role === 'student'; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function studentProfile() { return $this->hasOne(StudentProfile::class); }
    public function applications() { return $this->hasMany(ScholarshipApplication::class); }
    public function awards() { return $this->hasMany(ScholarshipAward::class); }
    public function notifications() { return $this->hasMany(AppNotification::class, 'user_id'); }
}
