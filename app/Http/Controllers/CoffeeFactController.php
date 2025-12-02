<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CoffeeFact;
use App\Models\Main\Coffee;
use App\Models\Main\Preference;


class CoffeeFactController extends Controller
{
    public function randomFact(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.'
            ], 401);
        }

        $fact = CoffeeFact::inRandomOrder()->value('fact');

        return response()->json([
            'fact' => $fact ?? "Coffee is amazing! ☕"
        ]);
    }

    public function recommendation(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.'
            ], 401);
        }

        $preference = Preference::where('user_id', $user->id)->first();
        if (!$preference) {
            return response()->json([
                'message' => 'No preference found for this user.'
            ], 404);
        }

        $coffees = Coffee::withAvg('ratings', 'rating')
            ->withCount(['favoritedBy as favorites_count', 'likedBy as likes_count'])
            ->get([
                'coffee_id',
                'coffee_name',
                'description',
                'ingredients',
                'coffee_type',
                'lactose',
                'nuts',
                'minimum_price',
                'maximum_price',
                'coffee_image'
            ]);

        $scoredCoffees = $coffees->map(function ($coffee) use ($preference) {

            $realRating = number_format($coffee->ratings_avg_rating ?? 0, 1);
            $likesCount = $coffee->likes_count ?? 0;
            $favoritesCount = $coffee->favorites_count ?? 0;

            $score = 0;
            $reasons = [];

            // ---------------- USER PREFERENCES ----------------
            if ($preference->coffee_type && $coffee->coffee_type == $preference->coffee_type) {
                $score += 40;
                $reasons[] = "Matches your preferred coffee type ({$preference->coffee_type})";
            }

            if ($preference->lactose && $coffee->lactose == 'no') {
                $score += 20;
                $reasons[] = "Lactose-free, suitable for your dietary restriction";
            }

            if ($preference->nuts_allergy && $coffee->nuts == 'no') {
                $score += 20;
                $reasons[] = "Nut-free, safe for your allergy";
            }

            if ($preference->coffee_allowance && $coffee->maximum_price <= $preference->coffee_allowance) {
                $score += 10;
                $reasons[] = "Within your budget of ₱{$preference->coffee_allowance}";
            }

            // ---------------- USER INTERACTIONS ----------------
            $score += min($realRating, 5) * 2;
            $score += min($likesCount, 50) / 5;

            if ($realRating > 0) $reasons[] = "Highly rated by users ({$realRating}⭐)";
            if ($likesCount > 0) $reasons[] = "Popular among users ({$likesCount} likes)";

            // ---------------- MOVING AVERAGE TREND ----------------
            // Example: get last 3 months of likes from database
            $monthlyLikes = DB::table('coffee_likes')
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->where('coffee_id', $coffee->coffee_id)
                ->where('created_at', '>=', now()->subMonths(3))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total')
                ->toArray();

            if (!empty($monthlyLikes)) {
                $trendForecast = $this->forecastNext3RollingMA($monthlyLikes);
                $trendScore = end($trendForecast); // last forecasted value
                $score += $trendScore / 5; // scale as needed
                $reasons[] = "Trending recently ({$trendScore} avg likes)";
            }

            // ---------------- IMAGE ----------------
            $coffee->coffee_image = $coffee->coffee_image
                ? asset('storage/' . $coffee->coffee_image)
                : null;

            // ---------------- OUTPUT ----------------
            $coffee->rating = $realRating;
            $coffee->likes = $likesCount;
            $coffee->favorites = $favoritesCount;
            $coffee->score = $score;
            $coffee->reasons = $reasons;

            return $coffee;
        });

        // Sort by score
        $topCoffees = $scoredCoffees->sortByDesc('score')->take(4)->values();

        if ($topCoffees->isEmpty()) {
            return response()->json([
                'message' => 'No suitable coffee found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Top prescriptive coffee recommendations fetched successfully.',
            'coffees' => $topCoffees
        ]);
    }

    // ---------------- HELPER FUNCTIONS ----------------
    private function forecastNext3RollingMA(array $series)
    {
        $forecast = [];
        $window = 3;
        $extended = $series;

        for ($i = 0; $i < 3; $i++) {
            $lastWindow = array_slice($extended, -$window);
            $next = round(array_sum($lastWindow) / count($lastWindow), 2);
            $forecast[] = $next;
            $extended[] = $next;
        }

        return $forecast;
    }
}
