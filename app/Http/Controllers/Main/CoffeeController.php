<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Main\Coffee;

class CoffeeController extends Controller
{
    public function index(Request $request)
    {
        $userId = optional($request->user())->id;

        $coffees = Coffee::withCount(['likedByUsers', 'favoritedByUsers'])
            ->with(['ratings' => function($q) use ($userId) {
                if ($userId) $q->where('user_id', $userId)->select('coffee_id', 'rating', 'user_id');
            }])->get();

            $userLikes = $userId
                ? $coffees->pluck('likedByUser')->flatten()->pluck('id')->all() : [];

            $userFavorites = $userId
                ? $coffees->pluck('likedByUser')->flatten()->pluck('id')->all() : [];

            $coffees->map(function($coffee) use ($userId, $userLikes, $userFavorites){
                $coffee->likedByUser = $userId && in_array($coffee->id, $userLikes);
                $coffee->favoritedByUser = $userId && in_array($coffee->id, $userFavorites);
                $coffee->userRating = optional($coffee->ratings->first())->rating;

                unset($coffee->ratings, $coffee->likedByUser, $coffee->favoritedByUsers);

                return $coffee;
            });

        return response()->json($coffees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|string',
            'nuts' => 'nullable|string',
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

    public function show(Request $request, Coffee $coffee)
    {

        $userId = optional($request->user())->id;

        $coffee->loadCount(['likedByUser', 'favoritedByUser'])
        ->load(['ratings' => function($q) use ($userId){
            if ($userId) $q->where('user_id', $userId)->select('coffee_id','rating','user_id');
        }]);
        
        $coffee->likedByUser = $userId && $coffee->likedByUsers->contains('id', $userId);
        $coffee->favoritedByUser = $userId && $coffee->favoritedByUsers->contains('id', $userId);
        $coffee->userRating = optional($coffee->ratings->first())->rating;

        unset($coffee->ratings, $coffee->likedByUsers, $coffee->favoritedByUsers);

        return response()->json([
            'status' => 'success',
            'coffee' => $coffee,
        ]);

    }

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

    public function destroy(Coffee $coffee)
    {
        $coffee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee deleted successfully',
        ]);
    }

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

        $coffee->loadcount('likedByUser');
        $totalLikes = $coffee->liked_by_users_count + ($coffee->likes ?? 0);

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

        $avgRating = round($coffee->ratings()->avg('rating'), 1);

        return response()->json([
            'rating' => $avgRating,
            'userRating' => $request->rating,
        ]);
    }

}
