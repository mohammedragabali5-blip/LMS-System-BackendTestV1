<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService {
    public static function log(string $action , ?Model $subject  = null , ?string $description  = null ,array $meta = []){
       return AuditLog::create([
            'user_id' =>auth()->id() ,
            'action' =>$action ,
             'auditable_type' => $subject ? get_class($subject) : null,
            'auditable_id' => $subject?->id,
            'description' => $description,
            'meta' => $meta,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}