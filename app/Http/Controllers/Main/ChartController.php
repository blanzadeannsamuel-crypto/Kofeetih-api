<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Main\Coffee;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    /**
     * Top 10 Recommended Coffees
     * (seed likes + pivot likes)
     */
    public function recommended()
    {
        $coffees = Coffee::withCount('likedByUsers')
            ->get()
            ->map(function ($coffee) {
                $coffee->total_likes = intval($coffee->likes ?? 0) + intval($coffee->liked_by_users_count);
                return $coffee;
            })
            ->sortByDesc('total_likes')
            ->take(10)
            ->values();

        return response()->json($coffees);
    }

    /**
     * Top 10 Rated Coffees
     */
    public function rated()
    {
        $coffees = Coffee::with('ratings')
            ->get()
            ->map(function ($coffee) {
                $avg = $coffee->ratings->avg('rating');
                $coffee->avg_rating = round($avg ?? 0, 2);
                unset($coffee->ratings);
                return $coffee;
            })
            ->sortByDesc('avg_rating')
            ->take(10)
            ->values();

        return response()->json($coffees);
    }

    /**
     * Top 10 Favorites
     * (seed favorites + pivot favorites)
     */
    public function favorites()
    {
        $coffees = Coffee::withCount('favoritedByUsers')
            ->get()
            ->map(function ($coffee) {
                $coffee->total_favorites = intval($coffee->favorites ?? 0) 
                                         + intval($coffee->favorited_by_users_count);
                return $coffee;
            })
            ->sortByDesc('total_favorites')
            ->take(10)
            ->values();

        return response()->json($coffees);
    }


    /**
     * Predictive: Exponential Smoothing
     */
    public function predictive(Request $request)
    {
        $alpha = floatval($request->query('alpha', 0.5));
        $forecastMonths = intval($request->query('months', 3));

        $coffees = Coffee::get()
            ->map(function ($coffee) use ($alpha, $forecastMonths) {

                // Ensure likes_history exists and is array
                $history = json_decode($coffee->likes_history ?? '[]', true);
                if (!is_array($history) || empty($history)) {
                    $history = [intval($coffee->likes ?? 0)];
                }

                $smoothed = [];
                $smoothed[0] = $history[0];

                for ($i = 1; $i < count($history); $i++) {
                    $smoothed[$i] =
                        $alpha * $history[$i] +
                        (1 - $alpha) * $smoothed[$i - 1];
                }

                $last = end($smoothed);
                $forecast = array_fill(0, $forecastMonths, $last);

                $coffee->history = $history;
                $coffee->smoothed = $smoothed;
                $coffee->forecast = $forecast;

                return $coffee;
            });

        return response()->json($coffees);
    }


    /**
     * Moving Average Forecast
     */
    public function movingAverage(Request $request)
    {
        $window = intval($request->query('window', 3));
        $forecastMonths = intval($request->query('months', 3));

        $coffees = Coffee::get()
            ->map(function ($coffee) use ($window, $forecastMonths) {

                $history = json_decode($coffee->likes_history ?? '[]', true);
                if (!is_array($history) || empty($history)) {
                    $history = [intval($coffee->likes ?? 0)];
                }

                $movingAverage = [];
                $count = count($history);

                for ($i = 0; $i < $count; $i++) {
                    if ($i < $window - 1) {
                        $movingAverage[] = null;
                    } else {
                        $slice = array_slice($history, $i - $window + 1, $window);
                        $movingAverage[] = array_sum($slice) / $window;
                    }
                }

                $lastMA = end(array_filter($movingAverage)) ?: $history[count($history)-1];
                $forecast = array_fill(0, $forecastMonths, $lastMA);

                $coffee->history = $history;
                $coffee->moving_average = $movingAverage;
                $coffee->forecast = $forecast;

                return $coffee;
            });

        return response()->json($coffees);
    }
}
