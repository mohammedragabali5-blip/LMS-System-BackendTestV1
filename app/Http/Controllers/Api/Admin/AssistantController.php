<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AssistantController extends Controller
{
    public function index()
    {
        return response()->json(User::where('role', 'assistant')->with('permissions')->paginate(20));
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = null;

        if ($request->hasFile('profile_picture')) {
            $imagePath = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        $user = User::create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'assistant',
            'status' => 'active',
            'profile_picture' => $imagePath,
        ]);

        $user->sendEmailVerificationNotification();

        AuditLogService::log(
            'user.created',
            $user,
            "Created {$user->role} {$user->email}"
        );

        return response()->json([
            'message' => 'Registration successful. Your account will be activated once a course is assigned.',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, User $assistant)
    {
        abort_unless($assistant->role === 'assistant', 404);

        $validator = Validator::make($request->all(), [
            'fname' => 'sometimes|string|max:255',
            'lname' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|email|unique:users,email,' . $assistant->id,

            'password' => 'nullable|string|min:8|confirmed',

            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('profile_picture')) {

            if (
                $assistant->profile_picture &&
                Storage::disk('public')->exists($assistant->profile_picture)
            ) {

                Storage::disk('public')->delete($assistant->profile_picture);
            }

            $data['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        $assistant->update($data);

        AuditLogService::log(
            'assistant.updated',
            $assistant,
            "Updated assistant {$assistant->email}",
            $data
        );

        return response()->json([
            'message' => 'Assistant updated successfully.',
            'user' => $assistant->fresh(),
        ]);
    }

    // AD-004
    public function destroy(User $assistant)
    {
        abort_unless($assistant->role === 'assistant', 404);

        AuditLogService::log('assistant.deleted', $assistant, "Deleted assistant {$assistant->email}");
        $assistant->tokens()->delete();
        $assistant->delete();

        return response()->json(['message' => 'Assistant deleted.']);
    }

    // AD-005
    public function assignPermissions(Request $request, User $assistant)
    {
        abort_unless($assistant->role === 'assistant', 404);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:manage_students,manage_courses,upload_videos,delete_courses,reports,settings',
        ]);

        $permissionIds = Permission::whereIn('key', $validated['permissions'])->pluck('id', 'key');

        $syncData = [];
        foreach ($permissionIds as $key => $id) {
            $syncData[$id] = ['granted_by' => $request->user()->id];
        }
        $assistant->permissions()->sync($syncData);

        AuditLogService::log('assistant.permissions_updated', $assistant, 'Updated permission set', $validated);

        return response()->json($assistant->load('permissions'));
    }
}
