<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
public function index(Request $request)
{
    $query = User::where('role', 'student')->withTrashed();

    // Search by name or email
    if ($search = trim((string) $request->query('search', ''))) {
        $query->where(function ($q) use ($search) {
            $q->where('fname', 'like', "%{$search}%")
              ->orWhere('lname', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(fname, ' ', lname) LIKE ?", ["%{$search}%"]);
        });
    }

    if ($status = $request->query('status')) {
        $query->where('status', $status);
    }

    $sortBy = $request->query('sort_by');
    $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc'
        ? 'desc'
        : 'asc';

    if ($sortBy === 'name') {
        $query->orderBy('fname', $sortDir)
              ->orderBy('lname', $sortDir);
    } elseif ($sortBy === 'email') {
        $query->orderBy('email', $sortDir);
    } else {
        $query->orderBy('created_at', 'desc');
    }

    $perPage = max((int) $request->query('per_page', 20), 1);

    return response()->json($query->paginate($perPage));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'fname' => 'required|string|max:255',
        'lname' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|max:30',
        'password' => 'required|string|min:8|confirmed',
        'profile_picture' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ],
    ]);

    $imagePath = null;

    if ($request->hasFile('profile_picture')) {
        $imagePath = $request
            ->file('profile_picture')
            ->store('profile_pictures', 'public');
    }

    $student = User::create([
        'fname' => $validated['fname'],
        'lname' => $validated['lname'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'password' => Hash::make($validated['password']),
        'role' => 'student',
        'status' => 'inactive',
        'profile_picture' => $imagePath,
    ]);

    $student->sendEmailVerificationNotification();

    AuditLogService::log('student.created', $student);

    return response()->json($student, 201);
}

    public function update(Request $request, User $student)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|email|unique:users,email,'.$student->id,
            'status' => 'sometimes|in:active,inactive,disabled',
        ]);

        $student->update($validated);
        AuditLogService::log('student.updated', $student, null, $validated);

        return response()->json($student);
    }

    public function destroy(User $student)
    {
        AuditLogService::log('student.deleted', $student);
        $student->delete();

        return response()->json(['message' => 'Student deleted.']);
    }

    public function restore(int $id)
    {
        $student = User::withTrashed()->findOrFail($id);
        $student->restore();

        AuditLogService::log('student.restored', $student);

        return response()->json($student);
    }
}
