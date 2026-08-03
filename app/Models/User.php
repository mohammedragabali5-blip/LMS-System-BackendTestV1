<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fname',
        'lname',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'profile_picture',
        'last_login_at',
    ];
    protected $appends = ['profile_picture_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function  isStudent()
    {
        return $this->role === 'student';
    }

    public function isAssistant()
    {
        return $this->role === 'assistant';
    }
    public function isAdmin()
    {
        return $this->role === "admin";
    }

    public function hasPermission(string $key)
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->permissions()->where('key', $key)->exists();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'assistant_permissions')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

public function enrollments()
{
    return $this->hasMany(Enrollment::class, 'user_id');
}
    public function coursesCreated()
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function deviceSessions()
    {
        return $this->hasMany(DeviceSession::class);
    }



public function sendEmailVerificationNotification()
{
    $this->notify(new CustomVerifyEmail());
}
public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPasswordNotification($token));
}

public function getProfilePictureUrlAttribute()
{
    if (!$this->profile_picture) {
        return null;
    }

    return asset('storage/' . $this->profile_picture);
}
}