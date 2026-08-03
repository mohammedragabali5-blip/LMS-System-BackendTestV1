<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
class PasswordController extends Controller
{
  public function sendResetLink(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }


    $email = Str::lower($request->email);


    // Create unique key for this email
    $key = 'password-reset:' . $email;


    // Check if request was sent within the last 60 seconds
    if (RateLimiter::tooManyAttempts($key, 1)) {

        $seconds = RateLimiter::availableIn($key);

        return response()->json([
            'message' => "Please wait {$seconds} seconds before requesting another reset email."
        ], 429);
    }


    // Block another request for 60 seconds
    RateLimiter::hit($key, 60);


    $status = Password::sendResetLink([
        'email' => $email
    ]);


    return $status === Password::RESET_LINK_SENT
        ? response()->json([
            'message' => __($status)
        ])
        : response()->json([
            'message' => __($status)
        ], 400);
}

    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => bcrypt($password)])->save();
                $user->tokens()->delete(); 
                NotificationService::passwordChanged($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }
}