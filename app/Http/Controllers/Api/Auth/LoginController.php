<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    protected const MAX_ACTIVE_DEVICES = 2;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_label' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'يرجى تأكيد عنوان بريدكم الإلكتروني قبل تسجيل الدخول إلى حسابكم'
            ], 403);
        }


        if ($user->status === 'disabled') {
            return response()->json(['message' => 'سيتم تفعيل حسابكم وإتاحة تسجيل الدخول إليه بعد تعيين الدورات (الكورسات) الخاصة بكم من قبل إدارة النظام.'], 403);
        }

        $activeSessions = $user->deviceSessions()->orderBy('last_used_at')->get();
        if ($activeSessions->count() >= self::MAX_ACTIVE_DEVICES) {
            $toRevoke = $activeSessions->take($activeSessions->count() - self::MAX_ACTIVE_DEVICES + 1);
            foreach ($toRevoke as $session) {
                $user->tokens()->where('id', $session->personal_access_token_id)->delete();
                $session->delete();
            }
        }

        $token = $user->createToken($request->device_label ?? 'default-device');

        DeviceSession::create([
            'user_id' => $user->id,
            'personal_access_token_id' => $token->accessToken->id,
            'device_label' => $request->device_label,
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
        ]);

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }


    public function logout(Request $request)
    {
        $tokenId = $request->user()->currentAccessToken()->id;

        DeviceSession::where('personal_access_token_id', $tokenId)->delete();
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}