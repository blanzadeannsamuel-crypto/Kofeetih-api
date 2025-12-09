<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLogsModel;
use App\Models\Main\Coffee;
use App\Models\Main\MustTryCoffee;

class UserController extends Controller
{
    
    public function fetchCoffee(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $userId = $user->id;

        $coffees = Coffee::with(['likedBy', 'favoritedBy'])
            ->whereHas('likedBy', fn($q) => $q->where('users.id', $userId))
            ->orWhereHas('favoritedBy', fn($q) => $q->where('users.id', $userId))
            ->get();

        $coffeeIds = $coffees->pluck('id');
        $feedbacks = MustTryCoffee::where('user_id', $userId)
            ->whereIn('coffee_id', $coffeeIds)
            ->pluck('comment', 'coffee_id');

        $coffees = $coffees->map(function ($coffee) use ($userId, $feedbacks) {
            return [
                'coffee_id'       => $coffee->coffee_id,
                'coffee_name'     => $coffee->coffee_name,
                'coffee_image'    => $coffee->coffee_image ? asset('storage/' . $coffee->coffee_image) : null,
                'coffee_type'     => $coffee->coffee_type,
                'likes'           => $coffee->likes ?? 0,
                'favorites'       => $coffee->favorites ?? 0,
                'likedByUser'     => $coffee->likedBy->contains('id', $userId),
                'favoritedByUser' => $coffee->favoritedBy->contains('id', $userId),
                'userFeedback'    => $feedbacks[$coffee->id] ?? null,
            ];
        });

        return response()->json($coffees);
    }


    public function index()
    {
        $users = User::select('id', 'first_name', 'display_name', 'birthdate', 'email')->get();

        return response()->json(
            $users->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->display_name ?: $u->first_name,
                'display_name' => $u->display_name,
                'first_name'   => $u->first_name,
                'birthdate'    => $u->birthdate,
                'age'          => $u->age,
                'email'        => $u->email,
            ])
        );
    }

    // =======================
    // Update Display Name
    // =======================
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

    // =======================
    // Get Current Authenticated User
    // =======================
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $preference = $user->preference;

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->display_name ?: $user->first_name,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'birthdate'    => $user->birthdate,
            'age'          => $user->age,
            'email'        => $user->email,
            'preference' => $preference ? [
                'coffee_type' => $preference->coffee_type,
                'coffee_allowance' => $preference->coffee_allowance,
                'serving_temp' => $preference->serving_temp,
                'lactose' => $preference->lactose ? 'Yes' : 'No',
                'nuts_allergy' => $preference->nuts_allergy ? 'Yes' : 'No',
            ] : null,
        ]);
    }

    // =======================
    // Update User Settings
    // =======================
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
            'user'    => $user->only(['first_name', 'email', 'display_name', 'age']),
        ]);
    }

    // =======================
    // Get Authenticated User With Age
    // =======================
    public function allUser(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id'           => $user->id,
            'last_name'    => $user->last_name,
            'first_name'   => $user->first_name,
            'display_name' => $user->display_name,
            'age'          => $user->age,
            'email'        => $user->email,
            'role'         => $user->role,
        ]);
    }
}
