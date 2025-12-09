<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Main\MustTryCoffee;
use Illuminate\Support\Facades\DB;

class MustTryCoffeeController extends Controller
{
    /**
     * Fetch all must-try coffees with comments
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $isAdmin = $request->user()->role === 'admin';

        $mustTry = MustTryCoffee::with(['coffee', 'user'])
            ->get()
            ->map(function ($item) use ($userId, $isAdmin) {
                // Fetch user's rating from coffee_ratings table
                $userRating = DB::table('coffee_ratings')
                    ->where('user_id', $item->user_id)
                    ->where('coffee_id', $item->coffee_id)
                    ->value('rating');

                return [
                    'id' => $item->id,
                    'coffee_id' => $item->coffee_id,
                    'comment' => $item->comment,
                    'user_rating' => $userRating, // user rating added
                    'updated_at' => $item->updated_at,
                    'user_id' => $item->user_id,
                    'user_display_name' => $item->user->display_name ?? $item->user->first_name,
                    'user_email' => $item->user->email,
                    'is_own_comment' => $item->user_id === $userId,
                    'can_delete' => $item->user_id === $userId || $isAdmin,
                    'coffee' => $item->coffee ? [
                        'coffee_id' => $item->coffee->coffee_id,
                        'coffee_name' => $item->coffee->coffee_name,
                        'coffee_type' => $item->coffee->coffee_type,
                        'coffee_image' => $item->coffee->coffee_image ? asset('storage/' . $item->coffee->coffee_image) : null,
                    ] : null,
                ];
            });

        return response()->json(['comments' => $mustTry]);
    }

    /**
     * Fetch comments for a specific coffee
     */
    public function show($coffeeId, Request $request)
    {
        $userId = $request->user()->id;
        $isAdmin = $request->user()->role === 'admin';

        $comments = MustTryCoffee::with('user')
            ->where('coffee_id', $coffeeId)
            ->get()
            ->map(function ($item) use ($userId, $isAdmin) {
                $userRating = DB::table('coffee_ratings')
                    ->where('user_id', $item->user_id)
                    ->where('coffee_id', $item->coffee_id)
                    ->value('rating');

                return [
                    'id' => $item->id,
                    'coffee_id' => $item->coffee_id,
                    'comment' => $item->comment,
                    'user_rating' => $userRating,
                    'user_id' => $item->user_id,
                    'user_display_name' => $item->user->display_name ?? $item->user->first_name,
                    'user_email' => $item->user->email,
                    'is_own_comment' => $item->user_id === $userId,
                    'can_delete' => $item->user_id === $userId || $isAdmin,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        return response()->json(['comments' => $comments]);
    }

    /**
     * Fetch current user's must try coffees (for profile page)
     */
    public function myMustTry(Request $request)
    {
        $userId = $request->user()->id;

        $mustTry = MustTryCoffee::with('coffee')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'coffee_id' => $item->coffee_id,
                    'comment' => $item->comment,
                    'coffee_name' => $item->coffee->coffee_name ?? null,
                    'coffee_type' => $item->coffee->coffee_type ?? null,
                    'coffee_image' => $item->coffee->coffee_image ? asset('storage/' . $item->coffee->coffee_image) : null,
                ];
            });

        return response()->json(['mustTryList' => $mustTry]);
    }

    /**
     * Add a coffee to Must Try list (no comment required)
     */
    public function addMustTry(Request $request)
    {
        $request->validate([
            'coffee_id' => 'required|exists:coffees,coffee_id',
        ]);

        $userId = $request->user()->id;

        $mustTry = MustTryCoffee::firstOrCreate(
            ['user_id' => $userId, 'coffee_id' => $request->coffee_id],
            ['comment' => null]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Added to Must Try list!',
            'mustTry' => $mustTry
        ]);
    }

    /**
     * Add a comment for a specific coffee in Must Try
     */
    public function addComment(Request $request, $coffeeId)
    {
        $request->validate([
            'comment' => 'required|string|max:150',
        ]);

        $user = $request->user();

        $mustTry = MustTryCoffee::firstOrCreate(
            ['user_id' => $user->id, 'coffee_id' => $coffeeId]
        );

        $mustTry->comment = $request->comment;
        $mustTry->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment added/updated successfully.',
            'mustTry' => $mustTry
        ]);
    }

    /**
     * Update a comment by MustTryCoffee ID
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:150',
        ]);

        $user = $request->user();

        $mustTry = MustTryCoffee::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$mustTry) {
            return response()->json([
                'status' => 'error',
                'message' => 'Comment not found or you are not authorized.',
            ], 404);
        }

        $mustTry->comment = $request->comment;
        $mustTry->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment updated successfully.',
            'mustTry' => $mustTry,
        ]);
    }

    /**
     * Delete must try entry (and comment)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $mustTry = MustTryCoffee::findOrFail($id);

        if ($mustTry->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $mustTry->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Entry deleted successfully.'
        ]);
    }
}
