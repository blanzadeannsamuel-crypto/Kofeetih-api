<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    // Get all users (for admin, optional)
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Update the authenticated user's display name
    public function updateDisplayName(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Must be authenticated

        if (!$user || !($user instanceof User)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->display_name = $request->input('display_name');
        $user->save(); // Save updated display name

        return response()->json([
            'message' => 'Display name updated successfully',
            'display_name' => $user->display_name,
        ]);
    }
}
