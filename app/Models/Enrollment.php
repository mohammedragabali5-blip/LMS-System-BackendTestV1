<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Enrollment extends Model
{
    use HasFactory ;
    protected $fillable = [
        'user_id', 'course_id', 'assigned_by', 'start_date', 'end_date', 'status', 'revoked_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function remainingDays(): int
    {
        return max(0, (int) Carbon::today()->diffInDays($this->end_date, false));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereDate('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')->orWhereDate('end_date', '<', now());
        });
    }
}