<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Permission extends Model
{
    use HasFactory ;
    public const MANAGE_STUDENTS = 'manage_students';
    public const MANAGE_COURSES = 'manage_courses';
    public const UPLOAD_VIDEOS = 'upload_videos';
    public const DELETE_COURSES = 'delete_courses';
    public const REPORTS = 'reports';
    public const SETTINGS = 'settings';

    protected $fillable = ['key', 'label'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'assistant_permissions');
    }
}
