<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8',
            'profile_picture' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // FIXED: previously the uploaded file was stored into $imagePath but
        // that variable was never attached to the created student, so the
        // upload silently went nowhere. Also, $validator->validated() would
        // have included the raw UploadedFile instance under 'profile_picture'
        // (not a storable string path) if spread directly into User::create().
        $data = $validator->validated();
        unset($data['profile_picture']);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        $student = User::create([
            ...$data,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'status' => 'active',
        ]);

        AuditLogService::log('student.created', $student, "Created student {$student->email}");

        return response()->json($student, 201);
    }

    public function update(Request $request, User $student)
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'fname' => 'sometimes|string|max:255',
            'lname' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|email|unique:users,email,'.$student->id,
            'profile_picture' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public');
        } else {
            unset($validated['profile_picture']);
        }

        $student->update($validated);
        AuditLogService::log('student.updated', $student, "Updated student {$student->email}", $validated);

        return response()->json($student);
    }

    // UA-004
    public function disable(User $student)
    {
        abort_unless($student->role === 'student', 404);

        $student->update(['status' => 'disabled']);
        $student->tokens()->delete();

        AuditLogService::log('student.disabled', $student, "Disabled student {$student->email}");

        return response()->json(['message' => 'Student disabled.']);
    }

    // NEW: reverse of disable() — re-activates a disabled student.
    public function enable(User $student)
    {
        abort_unless($student->role === 'student', 404);

        $student->update(['status' => 'active']);

        AuditLogService::log('student.enabled', $student, "Enabled student {$student->email}");

        return response()->json(['message' => 'Student enabled.']);
    }

    // NEW: soft delete
    public function destroy(User $student)
    {
        abort_unless($student->role === 'student', 404);

        $student->tokens()->delete();
        $student->delete();

        AuditLogService::log('student.deleted', $student, "Deleted student {$student->email}");

        return response()->json(['message' => 'Student deleted.']);
    }

    // NEW: restore a soft-deleted student. The route uses ->withTrashed()
    // so $student resolves even though it's currently soft-deleted.
    public function restore(User $student)
    {
        abort_unless($student->role === 'student', 404);

        $student->restore();

        AuditLogService::log('student.restored', $student, "Restored student {$student->email}");

        return response()->json($student);
    }

    // UA-005
    public function index(Request $request)
    {
        $query = User::where('role', 'student');

        // NEW: ?status=deleted surfaces soft-deleted students (needed so the
        // restore action has something to act on) instead of the normal
        // "active/inactive/disabled" filter, which only applies to
        // non-deleted rows.
        if ($request->query('status') === 'deleted') {
            $query->onlyTrashed();
        }

        // FIXED: was `$q->where('name', 'like', ...)` — this model has no
        // `name` column (it uses fname/lname), so any request that reached
        // this branch threw a SQL "unknown column" error. Also previously,
        // a malformed frontend call could make `$search` an array instead
        // of a string, which is truthy in PHP even when "empty" — worth
        // keeping in mind if this ever regresses.
        if ($search = $request->query('search')) {
            $search = is_array($search) ? '' : $search;
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
        }

        // Status filter, e.g. ?status=active|inactive|disabled
        // ('deleted' is handled above via onlyTrashed(), not a real status value)
        $status = $request->query('status');
        if ($status && $status !== 'deleted') {
            $query->where('status', $status);
        }

        // Sorting, e.g. ?sort_by=name|email&sort_dir=asc|desc
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortBy = $request->query('sort_by');

        if ($sortBy === 'name') {
            $query->orderBy('fname', $sortDir)->orderBy('lname', $sortDir);
        } elseif ($sortBy === 'email') {
            $query->orderBy('email', $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return response()->json(
            $query->paginate(20)->appends($request->query())
        );
    }

    // UA-006
    public function show(User $student)
    {
        abort_unless($student->role === 'student', 404);

        $student->load(['enrollments.course']);

        return response()->json([
            'student' => $student,
            'assigned_courses' => $student->enrollments->map(fn ($e) => [
                'enrollment_id' => $e->id,
                'course' => $e->course,
                'end_date' => $e->end_date,
                'status' => $e->status,
            ]),
            'last_login' => $student->last_login_at,
        ]);
    }
}
