<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 400);
        }


        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ]);
        }


        $user->markEmailAsVerified();


        return response()->json([
            'message' => 'Email verified successfully.'
        ]);
    }


    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {

            return response()->json([
                'message' => 'Email already verified.'
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent.'
        ]);
    }
}