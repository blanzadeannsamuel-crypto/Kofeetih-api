<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Main\Coffee;

class CoffeeController extends Controller
{
    /**
     * Display a listing of the coffees.
     * Includes user-specific like/favorite/rating info.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $coffees = Coffee::withCount(['likedByUsers', 'favoritedByUsers'])
            ->with(['ratings' => function($q) use ($user) {
                if ($user) $q->where('user_id', $user->id);
            }])->get();

        $coffees->each(function ($coffee) use ($user) {
            $coffee->likedByUser = $user ? $coffee->likedByUsers()->where('user_id', $user->id)->exists() : false;
            $coffee->favoritedByUser = $user ? $coffee->favoritedByUsers()->where('user_id', $user->id)->exists() : false;
            $coffee->userRating = optional($coffee->ratings->first())->rating;
            unset($coffee->ratings);
        });

        return response()->json($coffees);
    }

    /**
     * Store a newly created coffee.
     */
    public function store(Request $request)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|boolean',
            'nuts' => 'nullable|boolean',
            'minimum_price' => 'nullable|numeric|min:120',
            'maximum_price' => 'nullable|numeric|max:1200',
        ]);

        $coffee = Coffee::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee created successfully',
            'coffee' => $coffee,
        ], 201);
    }

    /**
     * Show a specific coffee with user info.
     */
    public function show(Request $request, Coffee $coffee)
    {
        $user = $request->user();
        $coffee->likedByUser = $user ? $coffee->likedByUsers()->where('user_id', $user->id)->exists() : false;
        $coffee->favoritedByUser = $user ? $coffee->favoritedByUsers()->where('user_id', $user->id)->exists() : false;
        $coffee->userRating = $user ? optional($coffee->ratings()->where('user_id', $user->id)->first())->rating : null;

        return response()->json([
            'status' => 'success',
            'coffee' => $coffee,
        ]);
    }

    /**
     * Update a coffee.
     */
    public function update(Request $request, Coffee $coffee)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|boolean',
            'nuts' => 'nullable|boolean',
            'minimum_price' => 'nullable|numeric|min:120',
            'maximum_price' => 'nullable|numeric|max:1200',
        ]);

        $coffee->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee updated successfully',
            'coffee' => $coffee,
        ]);
    }

    /**
     * Delete a coffee.
     */
    public function destroy(Coffee $coffee)
    {
        $coffee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee deleted successfully',
        ]);
    }

    /**
     * Toggle like for the authenticated user.
     */
    public function like(Request $request, Coffee $coffee)
    {
        $user = $request->user();

        $baseLikes = $coffee->likes ?? 0;

        if ($coffee->likedByUsers()->where('user_id', $user->id)->exists()) {
            $coffee->likedByUsers()->detach($user->id);
            $liked = false;
        } else {
            $coffee->likedByUsers()->attach($user->id);
            $liked = true;
        }

        // Total likes = seed likes + user likes
        $totalLikes = $baseLikes + $coffee->likedByUsers()->count();

        return response()->json([
            'likes' => $totalLikes,
            'liked' => $liked,
        ]);
    }

    public function favorite(Request $request, Coffee $coffee)
    {
        $user = $request->user();

        $baseFavorites = $coffee->favorites ?? 0;

        if ($coffee->favoritedByUsers()->where('user_id', $user->id)->exists()) {
            $coffee->favoritedByUsers()->detach($user->id);
            $favorited = false;
        } else {
            $coffee->favoritedByUsers()->attach($user->id);
            $favorited = true;
        }

        // Total favorites = seed favorites + user favorites
        $totalFavorites = $baseFavorites + $coffee->favoritedByUsers()->count();

        return response()->json([
            'favorites' => $totalFavorites,
            'favorited' => $favorited,
        ]);
    }

    public function rate(Request $request, Coffee $coffee)
    {
        $user = $request->user();

        $request->validate(['rating' => 'required|integer|min:1|max:5']);

        $coffee->ratings()->updateOrCreate(
            ['user_id' => $user->id],
            ['rating' => $request->rating]
        );

        // Average rating = include seed rating if you have it
        $avgRating = round($coffee->ratings()->avg('rating'), 1);
        // optional: you can add a base rating from seed if you want
        // $avgRating = round(($coffee->rating + $coffee->ratings()->avg('rating')) / 2, 1);

        return response()->json([
            'rating' => $avgRating,
            'userRating' => $request->rating,
        ]);
    }

}
