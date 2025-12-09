<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Main\Coffee;
use App\Models\Main\MustTryCoffee;
use App\Models\AuditLogsReport;

class CoffeeController extends Controller
{
    public function index(Request $request)
    {
        $userId = optional($request->user())->id;

        $coffees = Coffee::with(['ratings', 'likedBy', 'favoritedBy'])->get();

        $coffees = $coffees->map(function ($coffee) use ($userId) {
            $averageRating = $coffee->final_average_rating;
            $userRating = optional($coffee->ratings->where('user_id', $userId)->first())->rating;

            $userFeedback = $userId ? MustTryCoffee::where('user_id', $userId)
                                             ->where('coffee_id', $coffee->coffee_id)
                                             ->value('comment') : null;

            $inMustTry = $userId ? MustTryCoffee::where('user_id', $userId)
                                ->where('coffee_id', $coffee->coffee_id)
                                ->exists() : false;

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
                'price'            => $coffee->price,
                'likes'            => $coffee->likes,
                'favorites'        => $coffee->favorites,
                'rating'           => $averageRating,
                'likedByUser'      => $userId ? $coffee->likedBy->contains('id', $userId) : false,
                'favoritedByUser'  => $userId ? $coffee->favoritedBy->contains('id', $userId) : false,
                'userRating'       => $userRating,
                'userFeedback'     => $userFeedback,
                'inMustTry'        => $inMustTry,
            ];
        });

        return response()->json($coffees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'coffee_name' => 'required|string|max:255',
            'coffee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'coffee_type' => 'required|in:arabica,robusta,liberica',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'lactose' => 'nullable',
            'nuts' => 'nullable',
            'price' => 'required|numeric|min:0',
            'serving_temp' => 'nullable|in:hot,iced,both',
        ]);

        $data = $request->except('coffee_image');

        // Convert checkboxes to integers for MySQL
        $data['nuts'] = (int) filter_var($request->input('nuts', false), FILTER_VALIDATE_BOOLEAN);
        $data['lactose'] = (int) filter_var($request->input('lactose', false), FILTER_VALIDATE_BOOLEAN);
        $data['serving_temp'] = $request->input('serving_temp', 'hot');

        if ($request->hasFile('coffee_image')) {
            $data['coffee_image'] = $request->file('coffee_image')->store('coffee_image', 'public');
        }

        $coffee = Coffee::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee created successfully',
            'coffee' => $coffee->only([
                'coffee_id', 'coffee_name', 'coffee_image', 'description', 
                'ingredients', 'coffee_type', 'serving_temp', 'price', 'nuts', 'lactose'
            ]),
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

    public function update(Request $request, Coffee $coffee)
    {
        $request->validate([
            'coffee_name' => 'nullable|string|max:255',
            'coffee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'coffee_type' => 'nullable|in:arabica,robusta,liberica',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'lactose' => 'nullable',
            'nuts' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'serving_temp' => 'nullable|in:hot,iced,both',
        ]);

        $data = $request->except('coffee_image');

        // Convert checkboxes to integers
        if ($request->has('nuts')) {
            $data['nuts'] = (int) filter_var($request->input('nuts'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('lactose')) {
            $data['lactose'] = (int) filter_var($request->input('lactose'), FILTER_VALIDATE_BOOLEAN);
        }

        $data['serving_temp'] = $request->input('serving_temp', $coffee->serving_temp);

        if ($request->hasFile('coffee_image')) {
            if ($coffee->coffee_image) {
                Storage::disk('public')->delete($coffee->coffee_image);
            }
            $data['coffee_image'] = $request->file('coffee_image')->store('coffee_image', 'public');
        }

        $coffee->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Coffee updated successfully',
            'coffee' => $coffee->only([
                'coffee_id', 'coffee_name', 'coffee_image', 'description',
                'ingredients', 'coffee_type', 'serving_temp', 'price', 'nuts', 'lactose'
            ]),
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

        return DB::transaction(function () use ($coffee, $user) {

            $alreadyLiked = $coffee->likedBy()->where('user_id', $user->id)->exists();

            if ($alreadyLiked) {
                $coffee->likedBy()->detach($user->id);
                $coffee->decrement('likes');
                $liked = false;
            } else {
                $coffee->likedBy()->attach($user->id);
                $coffee->increment('likes');
                $liked = true;

                AuditLogsReport::create([
                    'user_id' => $user->id,
                    'type' => 'interaction',
                    'action' => 'LIKE COFFEE',
                    'description' => "{$user->display_name} liked {$coffee->coffee_name}"
                ]);
            }

            return response()->json([
                'likes' => $coffee->likes,
                'liked' => $liked,
            ]);
        });
    }

    public function favorite(Request $request, Coffee $coffee)
    {
        $user = $request->user();

        return DB::transaction(function () use ($coffee, $user) {
            $alreadyFavorited = $coffee->favoritedBy()->where('user_id', $user->id)->exists();

            if($alreadyFavorited){
                $coffee->favoritedBy()->detach($user->id);
                $coffee->decrement('favorites');
                $favorited = false;
            }else{
                $coffee->favoritedBy()->attach($user->id);
                $coffee->increment('favorites');
                $favorited = true;

                AuditLogsReport::create([
                    'user_id' => $user->id,
                    'type' => 'interaction',
                    'action' => 'FAVORITE COFFEE',
                    'description' => "{$user->display_name} favorited {$coffee->coffee_name}"
                ]);
            }

            return response()->json([
                'favorites' => $coffee->favorites,
                'favorited' => $favorited,
            ]);
        });
    }

    public function rate(Request $request, Coffee $coffee)
    {
        $user = $request->user();
        $request->validate(['rating' => 'required|integer|min:1|max:5']);

        return DB::transaction(function () use ($coffee, $user, $request) {

            $coffee->ratings()->updateOrCreate(
                ['user_id' => $user->id],
                ['rating' => $request->rating]
            );

            $coffee->load('ratings');

            AuditLogsReport::create([
                'user_id' => $user->id,
                'type' => 'interaction',
                'action' => 'RATE COFFEE',
                'description' => "{$user->display_name} rated {$coffee->coffee_name} with {$request->rating} stars"
            ]);

            return response()->json([
                'rating' => $coffee->final_average_rating,
                'userRating' => $request->rating
            ]);
        });
    }
}
