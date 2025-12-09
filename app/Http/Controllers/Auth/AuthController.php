<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLogsModel;

class AuthController extends Controller
{
    public function register(Request $request)
        {
            $request->validate([
        'last_name' => [
            'required',
            'string',
            'max:100',
            'regex:/^[a-zA-Z\s\-]+$/', 
        ],
        'first_name' => [
            'required',
            'string',
            'max:100',
            'regex:/^[a-zA-Z\s\-]+$/', 
        ],
        'display_name' => ['nullable','string','max:15'],
        'birthdate' => ['required','date'],
        'email' => ['required','string','email','max:255','unique:users'],
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
        ],
    ], [
        'last_name.regex' => 'Last name can only contain letters, spaces, and hyphens.',
        'first_name.regex' => 'First name can only contain letters, spaces, and hyphens.',
        'password.regex' => 'Password must include uppercase, lowercase, a number, and a special character.'
    ]);

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'display_name' => $request->display_name ?: 'coffee',
            'birthdate' => $request->birthdate,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Audit log for registration
        AuditLogsModel::create([
            'user_id' => $user->id,
            'type' => 'auth', // specify type
            'action' => 'register',
            'description' => 'User registered with email: ' . $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user->only(['id','last_name','first_name','display_name','email','role']),
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Audit log for failed login
            AuditLogsModel::create([
                'user_id' => $user ? $user->id : null,
                'type' => 'auth', // specify type
                'action' => 'failed_login',
                'description' => 'Failed login attempt for email: ' . $request->email,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        // Audit log for successful login
        AuditLogsModel::create([
            'user_id' => $user->id,
            'type' => 'auth', // specify type
            'action' => 'login',
            'description' => 'User logged in with email: ' . $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'user' => $user->only(['id','last_name','first_name','display_name','email','role']),
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();

            // Audit log for logout
            AuditLogsModel::create([
                'user_id' => $request->user()->id,
                'type' => 'auth', // specify type
                'action' => 'logout',
                'description' => 'User logged out: ' . $request->user()->email,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Logged out successfully'], 200);
    }
}
