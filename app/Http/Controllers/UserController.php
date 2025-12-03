<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLogsModel;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'first_name', 'last_name', 'display_name', 'email', 'age')->get();

        return response()->json(
            $users->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->display_name ?: $u->first_name,
                'display_name' => $u->display_name,
                'first_name'   => $u->first_name,
                'last_name'    => $u->last_name,
                'email'        => $u->email,
                'age'          => $u->age,
            ])
        );
    }

    public function updateDisplayName(Request $request)
    {
        $request->validate(['display_name' => 'required|string|max:15']);
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $oldName = $user->display_name;
        $user->update(['display_name' => $request->display_name]);

        AuditLogsModel::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_DISPLAY_NAME',
            'description' => "User updated display name from '{$oldName}' to '{$user->display_name}'"
        ]);

        return response()->json([
            'message' => 'Display name updated successfully',
            'display_name' => $user->display_name,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        return response()->json([
            'id' => $user->id,
            'name' => $user->display_name ?: $user->first_name,
            'display_name' => $user->display_name, 
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'age' => $user->age,
            'email' => $user->email,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $validated = $request->validate([
            'first_name'   => 'nullable|string|max:50',
            'email'        => 'nullable|email|unique:users,email,' . $user->id,
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        $changes = [];

        if (!empty($validated['new_password'])) {
            if (empty($validated['old_password']) || !Hash::check($validated['old_password'], $user->password)) {
                return response()->json(['error' => 'Old password is incorrect.'], 422);
            }
            $user->password = bcrypt($validated['new_password']);
            $changes[] = 'password';
        }

        if (!empty($validated['first_name']) && $validated['first_name'] !== $user->first_name) {
            $changes[] = "first_name from '{$user->first_name}' to '{$validated['first_name']}'";
            $user->first_name = $validated['first_name'];
        }

        if (!empty($validated['email']) && $validated['email'] !== $user->email) {
            $changes[] = "email from '{$user->email}' to '{$validated['email']}'";
            $user->email = $validated['email'];
        }

        $user->save();

        if (!empty($changes)) {
            AuditLogsModel::create([
                'user_id' => $user->id,
                'action' => 'UPDATE_SETTINGS',
                'description' => 'User updated settings: ' . implode(', ', $changes)
            ]);
        }

        return response()->json([
            'message' => 'Credentials updated successfully',
            'user'    => $user->only(['first_name', 'email', 'display_name']),
        ]);
    }

    public function allUser(Request $request)
    {
        $user = $request->user();

        return response()->json($user->only([
            'id', 'last_name', 'first_name', 'display_name', 'age', 'email', 'role',
        ]));    
    }
}
