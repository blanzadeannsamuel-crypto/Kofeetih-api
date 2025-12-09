<?php
namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Main\Coffee;
use App\Models\User;

class ChartController extends Controller
{
    public function summaryChart(Request $request)
    {
        $currentYear = date('Y');
        $months = range(1, 12);
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        // Enum values
        $coffeeTypes = ['arabica', 'robusta', 'liberica'];
        $servingTemps = ['hot', 'iced', 'both'];

        // Cache monthly aggregated preferences
        $monthlyPreference = Cache::remember("monthlyPreference_{$currentYear}", 3600, function() use ($currentYear) {
            return DB::table('preferences')
                ->selectRaw('MONTH(created_at) as month, LOWER(coffee_type) as coffee_type, LOWER(serving_temp) as serving_temp, COUNT(*) as total')
                ->whereYear('created_at', $currentYear)
                ->groupBy('month', 'coffee_type', 'serving_temp')
                ->get();
        });

        $monthlyData = ['coffee_types' => [], 'serving_temp' => []];
        $monthlyForecast = ['coffee_types' => [], 'serving_temp' => []];
        $monthlyMovingAvg = ['coffee_types' => [], 'serving_temp' => []];
        $exponentialCoffee = [];
        $exponentialTemp = [];

        // Helper to build forecast for series
        $buildSeriesForecast = function(array $series, $currentYear) {
            $movingAvg = $this->movingAverageSeries($series, 3);
            $maForecast = $this->forecastNext3RollingMA(array_slice($series, -3));
            $expResult = $this->forecastExponential(array_slice($series, -4), 0.4);
            $expForecast = [$expResult['next']];
            $maLabels = ['Jan ' . ($currentYear + 1), 'Feb ' . ($currentYear + 1), 'Mar ' . ($currentYear + 1)];
            $expLabel = ['Jan ' . ($currentYear + 1)];

            return [
                'series' => $series,
                'moving_avg' => $movingAvg,
                'ma_forecast' => $maForecast,
                'ma_labels' => $maLabels,
                'exp_forecast' => $expForecast,
                'exp_labels' => $expLabel,
                'exp_history' => $expResult['history'],
                'next_forecast' => $expResult['next']
            ];
        };

        // Process coffee types
        foreach ($coffeeTypes as $type) {
            $series = array_map(function($m) use ($monthlyPreference, $type) {
                $match = $monthlyPreference->first(fn($r) => intval($r->month) === $m && ($r->coffee_type ?? '') === $type);
                return intval($match->total ?? 0);
            }, $months);

            $label = ucfirst($type);
            $forecast = $buildSeriesForecast($series, $currentYear);

            $monthlyData['coffee_types'][$label] = $series;
            $monthlyMovingAvg['coffee_types'][$label] = $forecast['moving_avg'];
            $monthlyForecast['coffee_types'][$label] = $forecast;
            $exponentialCoffee[$label] = $forecast['next_forecast'];
        }

        // Process serving temps
        foreach ($servingTemps as $temp) {
            $series = array_map(function($m) use ($monthlyPreference, $temp) {
                $match = $monthlyPreference->first(fn($r) => intval($r->month) === $m && ($r->serving_temp ?? '') === $temp);
                return intval($match->total ?? 0);
            }, $months);

            // Use lowercase 'both' internally but display proper label
            $label = $temp === 'both' ? 'Hot, Iced' : ucfirst($temp);

            $forecast = $buildSeriesForecast($series, $currentYear);

            $monthlyData['serving_temp'][$label] = $series;
            $monthlyMovingAvg['serving_temp'][$label] = $forecast['moving_avg'];
            $monthlyForecast['serving_temp'][$label] = $forecast;
            $exponentialTemp[$label] = $forecast['next_forecast'];
        }

        // Determine trends
        $trendCoffee = array_keys($exponentialCoffee, max($exponentialCoffee))[0];
        $trendTemp = array_keys($exponentialTemp, max($exponentialTemp))[0];

        // Moving average forecasts
        $maCoffeeForecasts = array_map(fn($f) => end($f['ma_forecast']), $monthlyForecast['coffee_types']);
        $maTempForecasts = array_map(fn($f) => end($f['ma_forecast']), $monthlyForecast['serving_temp']);

        $trendCoffeeMA = array_keys($maCoffeeForecasts, max($maCoffeeForecasts))[0];
        $trendTempMA = array_keys($maTempForecasts, max($maTempForecasts))[0];

        // Format trend text for "both" temperature
        $trendTempText = ($trendTemp === 'Hot, Iced') ? 'both Hot and Iced' : $trendTemp;
        $trendTempMAText = ($trendTempMA === 'Hot, Iced') ? 'both Hot and Iced' : $trendTempMA;

        $trendReport = [
            'coffee_type' => "Coffee type trend for next month is {$trendCoffee} with forecasted value of {$exponentialCoffee[$trendCoffee]}.",
            'temperature' => "Serving Temperature trend for next month is {$trendTempText} with forecasted value of {$exponentialTemp[$trendTemp]}.",
            'coffee_type_ma' => "The coffee type trend 3 months from now is {$trendCoffeeMA} with forecasted value of {$maCoffeeForecasts[$trendCoffeeMA]}.",
            'temperature_ma' => "The serving temperature trend 3 months from now is {$trendTempMAText} with forecasted value of {$maTempForecasts[$trendTempMA]}."
        ];

        // Top 10 coffees by likes
        $topByLikes = Coffee::withCount('likedBy')
            ->get()
            ->map(fn($c) => [
                'coffee_id' => $c->coffee_id,
                'coffee_name' => $c->coffee_name,
                'coffee_image' => $c->coffee_image ? asset('storage/' . $c->coffee_image) : null,
                'total_likes' => intval($c->liked_by_count ?? 0)
            ])
            ->sortByDesc('total_likes')
            ->take(10)
            ->values();

        $topLikedCoffee = $topByLikes->first();

        // Top 10 coffees by rating
        $topByRating = Coffee::withAvg('ratings', 'rating')
            ->get()
            ->map(fn($c) => [
                'coffee_id' => $c->coffee_id,
                'coffee_name' => $c->coffee_name,
                'coffee_image' => $c->coffee_image ? asset('storage/' . $c->coffee_image) : null,
                'avg_rate' => round(floatval($c->ratings_avg_rating ?? 0), 1)
            ])
            ->sortByDesc('avg_rate')
            ->take(10)
            ->values();

        $topRatedCoffee = $topByRating->first(); // <-- added top-rated coffee

        return response()->json([
            'monthlyData' => $monthlyData,
            'monthlyForecast' => $monthlyForecast,
            'monthlyMovingAvg' => $monthlyMovingAvg,
            'monthLabels' => $monthLabels,
            'currentYear' => $currentYear,
            'topByLikes' => $topByLikes,
            'topLikedCoffee' => $topLikedCoffee,
            'topByRating' => $topByRating,
            'topRatedCoffee' => $topRatedCoffee, // <-- included in response
            'userCount' => User::count(),
            'coffeeCount' => Coffee::count(),
            'trendReport' => $trendReport
        ]);
    }

    private function movingAverageSeries(array $series, int $window = 3)
    {
        $n = count($series);
        if ($n === 0) return [];
        $ma = [];
        for ($i = 0; $i < $n; $i++) {
            $slice = array_slice($series, max(0, $i - $window + 1), min($window, $i + 1));
            $ma[] = round(array_sum($slice) / count($slice), 2);
        }
        return $ma;
    }

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

    private function forecastExponential(array $series, float $alpha = 0.4)
    {
        if (empty($series)) return ['history' => [], 'next' => 0];

        $n = count($series);
        $forecasts = [];
        $forecasts[0] = $series[0];

        for ($i = 1; $i < $n; $i++) {
            $forecasts[$i] = round($alpha * $series[$i] + (1 - $alpha) * $forecasts[$i - 1], 2);
        }

        $nextForecast = round($alpha * end($series) + (1 - $alpha) * end($forecasts), 2);

        return [
            'history' => $forecasts,
            'next' => $nextForecast
        ];
    }
}
