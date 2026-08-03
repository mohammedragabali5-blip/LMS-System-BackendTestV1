<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;


class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,fname,lname,email,phone')->latest('created_at');

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        return response()->json($query->paginate(50));
    }
}
