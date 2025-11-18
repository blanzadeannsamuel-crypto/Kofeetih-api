<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request ->validate([
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'age' => 'required|integer|min:13|max:99',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'age' => $request->age,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user->only(['id', 'last_name', 'first_name', 'email', 'role']),
        ], 200);
    }

    public function login(Request $request){
        
        $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        
        if(!Auth::attempt($request->only('email', 'password'))){
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ],401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'user' => $user->only(['id', 'last_name', 'first_name', 'email', 'role']),
            'token' => $token,
        
        ], 200);
    }

    public function logout(Request $request){

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'logged out successfully',
        ], 200);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json($user->only([
            'id', 'last_name', 'first_name', 'email', 'role',
        ]));    
    }
}
