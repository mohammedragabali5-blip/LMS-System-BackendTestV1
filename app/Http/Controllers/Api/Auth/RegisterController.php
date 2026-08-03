<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request){
        $imagePath = null ; 
         $validator =  Validator::make(
            $request->all(), [
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
        ]
        );
         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
         if ($request->hasFile('profile_picture')) {
            $imagePath = $request
                ->file('profile_picture')
                ->store('profile_pictures', 'public');
        }
         $user = User::create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'status' => 'inactive',
             'profile_picture' => $imagePath,
        ]);
       
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registration successful. Your account will be activated once a course is assigned.',
            'user' => $user,
        ], 201);
    }
}