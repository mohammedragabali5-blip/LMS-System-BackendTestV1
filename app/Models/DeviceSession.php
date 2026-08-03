<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class DeviceSession extends Model
{
    use HasFactory; 
    protected $fillable = ['user_id', 'personal_access_token_id', 'device_label', 'ip_address', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}