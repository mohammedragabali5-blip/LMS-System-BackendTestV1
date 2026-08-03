<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'fname' => 'sometimes|string|max:255',
            'lname' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'password' => 'sometimes|string|min:8|confirmed',
            'profile_picture' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            NotificationService::passwordChanged($user);
        }

        if ($request->hasFile('profile_picture')) {
            // delete old picture if it exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $validated['profile_picture'] = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public'); // same folder name as register
        }

        $user->update($validated);

    \Log::info('method check', [
        'real_method' => $request->getRealMethod(), // should be POST when spoofed correctly
        'method' => $request->method(),               // should be PUT (as Laravel sees it)
        'has_file' => $request->hasFile('profile_picture'),
    ]);
 

        return response()->json($user->fresh());
    }
}
