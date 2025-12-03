<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Main\Coffee;

class CoffeeController extends Controller
{
    public function index(Request $request)
    {
        $userId = optional($request->user())->id;


        $coffees = Coffee::with(['ratings', 'likedBy', 'favoritedBy'])->get();

        $coffees = $coffees->map(function ($coffee) use ($userId) {
            $averageRating = $coffee->final_average_rating;
            $userRating = optional($coffee->ratings->where('user_id', $userId)->first())->rating;

            return [
                'coffee_id'        => $coffee->coffee_id,
                'coffee_name'      => $coffee->coffee_name,
                'coffee_image'     => $coffee->coffee_image ? asset('storage/' . $coffee->coffee_image) : null,
                'description'      => $coffee->description,
                'ingredients'      => $coffee->ingredients,
                'coffee_type'      => $coffee->coffee_type,
                'serving_temp'     => $coffee->serving_temp,
                'nuts'             => $coffee->nuts,
                'lactose'          => $coffee->lactose,
                'minimum_price'    => $coffee->minimum_price,
                'maximum_price'    => $coffee->maximum_price,
                'likes'            => $coffee->likes,
                'favorites'        => $coffee->favorites,
                'rating'           => $averageRating,
                'likedByUser'      => $userId ? $coffee->likedBy->contains('id', $userId) : false,
                'favoritedByUser'  => $userId ? $coffee->favoritedBy->contains('id', $userId) : false,
                'userRating'       => $userRating,
            ];
        });

        return response()->json($coffees);
    }

    //this part is just a back up for adding coffee pero function lang naman natin likes, favorite, rates.
    public function store(Request $request)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'coffee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'coffee_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'lactose' => 'nullable|string',
            'nuts' => 'nullable|string',
            'price' => 'required|numeric|min:120',
        ]);

        $data = $request->except('coffee_image');
        $data['serving_temp'] = $request->input('serving_temp', 'hot');

        if ($request->hasFile('coffee_image')) {
            $data['coffee_image'] = $request->file('coffee_image')->store('coffee_image', 'public');
        }

        $coffee = Coffee::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee created successfully',
            'coffee' => $coffee->only(['coffee_id', 'coffee_name', 'coffee_image', 'description', 'ingredients', 'coffee_type', 'serving_temp', 'price']),
        ], 201);
    }

    public function show(Request $request, Coffee $coffee)
    {
        $userId = optional($request->user())->id;

        $coffee->loadCount(['likedBy', 'favoritedBy'])
               ->load(['ratings' => function($q) use ($userId) {
                    if ($userId) $q->where('user_id', $userId);
               }]);

        $coffee->coffee_image = $coffee->coffee_image ? asset('storage/' . $coffee->coffee_image) : null;
        $coffee->likedByUser = $userId ? $coffee->likedBy->contains('id', $userId) : false;
        $coffee->favoritedByUser = $userId ? $coffee->favoritedBy->contains('id', $userId) : false;
        $coffee->userRating = optional($coffee->ratings->first())->rating;

        unset($coffee->ratings, $coffee->likedBy, $coffee->favoritedBy);

        return response()->json([
            'status' => 'success',
            'coffee' => $coffee,
        ]);
    }

    //this is for just in case part if needed
    public function update(Request $request, Coffee $coffee)
    {
        $request->validate([
            'coffee_name' => 'nullable|string|max:255',
            'coffee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'coffee_type' => 'nullable|string|max:100',
            'lactose' => 'nullable|boolean',
            'nuts' => 'nullable|boolean',
            'minimum_price' => 'nullable|numeric|min:120',
            'maximum_price' => 'nullable|numeric|max:1200',
        ]);

        $data = $request->except('coffee_image');
        $data['serving_temp'] = $request->input('serving_temp', $coffee->serving_temp);

        if ($request->hasFile('coffee_image')) {
            if ($coffee->coffee_image){
                Storage::disk('public')->delete($coffee->coffee_image);
            }
             
            $data['coffee_image'] = $request->file('coffee_image')->store('coffee_image', 'public');
        }

        $coffee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee updated successfully',
            'coffee' => $coffee,
        ]);
    }

    // Delete coffee
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
        $userId = $request->user()->id;

            return DB::transaction(function () use ($coffee, $userId) {

            $alreadyLiked = $coffee->likedBy()->where('user_id', $userId)->exists();

            if ($alreadyLiked) {
                $coffee->likedBy()->detach($userId);
                $coffee->decrement('likes');
                $liked = false;
            } else {
                $coffee->likedBy()->attach($userId);
                $coffee->increment('likes');
                $liked = true;
            }

            return response()->json([
                'likes' => $coffee->likes,
                'liked' => $liked,
            ]);
        });
    }

    public function favorite(Request $request, Coffee $coffee)
    {
        $userId = $request->user()->id;

        return DB::transaction(function () use ($coffee, $userId) {
            $alreadyFavorited = $coffee->favoritedBy()->where('user_id', $userId)->exists();

            if($alreadyFavorited){
                $coffee->favoritedBy()->detach($userId);
                $coffee->decrement('favorites');
                $favorited = false;
            }else{
                $coffee->favoritedBy()->attach($userId);
                $coffee->increment('favorites');
                $favorited = true;
            }

            return response()->json([
                'favorites' => $coffee->favorites,
                'favorited' => $favorited,
            ]);
        });
    }

    public function rate(Request $request, Coffee $coffee)
    {
        $userId = $request->user()->id;
        $request->validate(['rating' => 'required|integer|min:1|max:5']);

        return DB::transaction(function () use ($coffee, $userId, $request) {

            $coffee->ratings()->updateOrCreate(
                ['user_id' => $userId],
                ['rating' => $request->rating]
            );

            $coffee->load('ratings');

            return response()->json([
                'rating' =>$coffee->final_average_rating,
                'userRating' => $request->rating
            ]);
        });
    }
    
}
