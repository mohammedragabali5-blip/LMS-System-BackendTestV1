<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class LessonProgress extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'lesson_id', 'last_position_seconds', 'watched_percentage', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'watched_percentage' => 'decimal:2',
    ];
 public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

}