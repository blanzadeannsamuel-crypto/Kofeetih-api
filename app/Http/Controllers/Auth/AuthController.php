<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function login(Request $request){
        
        $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|string',
        ]);

        $user=User::where('email',$request->email)->first();

        if(!$user || ! Hash::check($request->password,$user->password)){
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ],401);
        }


        $token=$user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'user' => $user,
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

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'display_name' => $user->display_name,
            // include other fields if needed
        ]);
    }
}
