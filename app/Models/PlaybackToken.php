<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PlaybackToken extends Model
{
    use HasFactory ; 
        protected $fillable = ['user_id', 'lesson_id', 'token', 'expires_at', 'used_at'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

      public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
    }