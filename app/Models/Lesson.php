<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'title',
        'video_path',
        'pdf_path',
        'attachments',
        'storage_disk',
        'order_index',
        'allow_attachments',
        'allow_download',
        'duration_seconds',
    ];

    protected $casts = [
        'attachments' => 'array',
        'allow_attachments' => 'boolean',
        'allow_download' => 'boolean',
    ];

    protected $hidden = ['file_path', 'storage_disk'];

    protected $appends = ['pdf_path_url'];


   public function getPdfPathUrlAttribute()
{
    if (!$this->pdf_path) {
        return null;
    }

    return route('lesson.pdf', [
        'course' => $this->course_id,
        'lesson' => $this->id,
    ]);
}

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
