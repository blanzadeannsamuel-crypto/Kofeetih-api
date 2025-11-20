<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Main\Coffee;

class CoffeeController extends Controller
{
    public function index(Request $request)
    {
        $userId = optional($request->user())->id;

        $coffees = Coffee::select(
        'id', 'coffee_name', 'image_url', 'description', 'ingredients',
        'coffee_type', 'lactose', 'minimum_price', 'maximum_price', 'likes', 'rating', 'favorites'
            )
            ->withCount(['likedByUsers', 'favoritedByUsers'])
            ->with(['ratings' => function($q) use ($userId) {
                if ($userId) {$q->where('user_id', $userId)->select('coffee_id', 'rating', 'user_id');
                }
            },
            'likedByUsers:id',
            'favoritedByUsers:id'
        ])->get();

            // $userLikes = $userId
            //     ? $coffees->pluck('likedByUser')->flatten()->pluck('id')->all() : [];

            // $userFavorites = $userId
            //     ? $coffees->pluck('likedByUser')->flatten()->pluck('id')->all() : [];

            $coffees->each(function($coffee) use ($userId){
                $coffee->image_url = $coffee->image_url ? asset('storage/' . $coffee->image_url) : null;
                $coffee->likedByUser = $userId ? $coffee->likedByUsers->contains($userId) : false;
                $coffee->favoritedByUser = $userId ? $coffee->favoritedByUsers->contains($userId) : false;
                $coffee->userRating = optional($coffee->ratings->first())->rating;

                unset($coffee->ratings, $coffee->likedByUsers, $coffee->favoritedByUsers);

                return $coffee;
            });

        return response()->json($coffees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|string',
            'nuts' => 'nullable|string',
            'minimum_price' => 'nullable|numeric|min:120',
            'maximum_price' => 'nullable|numeric|max:1200',
        ]);

        $data = $request->all();

        if($request->hasFile('image_url')){
            $path = $request->file('image_url')->store('coffee_image', 'public');
            $data['image_url'] = $path;
        }

        $coffee = Coffee::create($data);

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

        $coffee->image_url = $coffee->image_url ? asset('storage/' . $coffee->image_url) : null;
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
            'coffee_name' => 'nullable|string|max:255',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|boolean',
            'nuts' => 'nullable|boolean',
            'minimum_price' => 'nullable|numeric|min:120',
            'maximum_price' => 'nullable|numeric|max:1200',
        ]);

        $data = $request->except('image_url');

        if ($request->hasFile('image_url')) {

        if ($coffee->image_url && Storage::disk('public')->exists($coffee->image_url)) {
                Storage::disk('public')->delete($coffee->image_url);
            }
            $path = $request->file('image_url')->store('coffee_image', 'public');
            $data['image_url'] = $path;
        }

        $coffee = Coffee::update($data);

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

        if ($coffee->likedByUsers()->where('user_id', $user->id)->exists()) {
            $coffee->likedByUsers()->detach($user->id);
            $liked = false;
        } else {
            $coffee->likedByUsers()->attach($user->id);
            $liked = true;
        }


        $coffee->loadcount('likedByUsers');
        $totalLikes = $coffee->liked_by_users_count + ($coffee->likes ?? 0);

        return response()->json([
            'likes' => $totalLikes,
            'liked' => $liked,
        ]);
    }

    public function favorite(Request $request, Coffee $coffee)
    {
        $user = $request->user();


        if ($coffee->favoritedByUsers()->where('user_id', $user->id)->exists()) {
            $coffee->favoritedByUsers()->detach($user->id);
            $favorited = false;
        } else {
            $coffee->favoritedByUsers()->attach($user->id);
            $favorited = true;
        }

        $coffee->loadCount('favoritedByUsers');
        $totalFavorites = $coffee->favorited_by_users_count;

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
