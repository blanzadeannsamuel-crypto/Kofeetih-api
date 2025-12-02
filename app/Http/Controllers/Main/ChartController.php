<?php
namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Main\Coffee;
use App\Models\User;

class ChartController extends Controller
{
    public function summaryChart(Request $request)
    {
        $currentYear = date('Y');
        $typeNames = ['strong', 'balanced', 'sweet'];
        $tempNames = ['hot', 'cold'];
        $months = range(1, 12);
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $monthlyPreference = DB::table('preferences')
            ->selectRaw('MONTH(created_at) as month, LOWER(coffee_type) as coffee_type, LOWER(temp) as temp, COUNT(*) as total')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month', 'coffee_type', 'temp')
            ->get();

        $monthlyData = ['coffee_types' => [], 'temperature' => []];
        $monthlyForecast = ['coffee_types' => [], 'temperature' => []];
        $monthlyMovingAvg = ['coffee_types' => [], 'temperature' => []];

        // ---------------- COFFEE TYPES ----------------
        $exponentialCoffee = [];
        foreach ($typeNames as $type) {
            $label = ucfirst($type);
            $series = array_map(function ($m) use ($monthlyPreference, $type) {
                $match = $monthlyPreference->first(fn($r) => intval($r->month) === $m && ($r->coffee_type ?? '') === $type);
                return intval($match->total ?? 0);
            }, $months);

            $monthlyData['coffee_types'][$label] = $series;

            // ---------------- MOVING AVERAGE ----------------
            $monthlyMovingAvg['coffee_types'][$label] = $this->movingAverageSeries($series, 3);

            $maForecast = $this->forecastNext3RollingMA(array_slice($series, -3)); // Oct-Dec
            $maLabels = ['Jan ' . ($currentYear + 1), 'Feb ' . ($currentYear + 1), 'Mar ' . ($currentYear + 1)];

            // ---------------- EXPONENTIAL ----------------
            $expResult = $this->forecastExponential(array_slice($series, -4), 0.4); // Sep-Dec
            $expForecast = [$expResult['next']]; // forecast for Jan
            $expLabel = ['Jan ' . ($currentYear + 1)];
            $exponentialCoffee[$label] = $expForecast[0];

            $monthlyForecast['coffee_types'][$label] = [
                'ma_labels' => $maLabels,
                'ma_values' => $maForecast,
                'exp_labels' => $expLabel,
                'exp_values' => $expForecast,
                'exp_history' => $expResult['history'] // optional: Sep-Dec forecasted history
            ];
        }

        // ---------------- TEMPERATURE ----------------
        $exponentialTemp = [];
        foreach ($tempNames as $temp) {
            $label = ucfirst($temp);
            $series = array_map(function ($m) use ($monthlyPreference, $temp) {
                $match = $monthlyPreference->first(fn($r) => intval($r->month) === $m && ($r->temp ?? '') === $temp);
                return intval($match->total ?? 0);
            }, $months);

            $monthlyData['temperature'][$label] = $series;

            // ---------------- MOVING AVERAGE ----------------
            $monthlyMovingAvg['temperature'][$label] = $this->movingAverageSeries($series, 3);

            $maForecast = $this->forecastNext3RollingMA(array_slice($series, -3)); // Oct-Dec
            $maLabels = ['Jan ' . ($currentYear + 1), 'Feb ' . ($currentYear + 1), 'Mar ' . ($currentYear + 1)];

            // ---------------- EXPONENTIAL ----------------
            $expResult = $this->forecastExponential(array_slice($series, -4), 0.4); // Sep-Dec
            $expForecast = [$expResult['next']]; // forecast for Jan
            $expLabel = ['Jan ' . ($currentYear + 1)];
            $exponentialTemp[$label] = $expForecast[0];

            $monthlyForecast['temperature'][$label] = [
                'ma_labels' => $maLabels,
                'ma_values' => $maForecast,
                'exp_labels' => $expLabel,
                'exp_values' => $expForecast,
                'exp_history' => $expResult['history']
            ];
        }

        // ---------------- TREND REPORT ----------------
        $trendCoffee = array_keys($exponentialCoffee, max($exponentialCoffee))[0];
        $trendTemp = array_keys($exponentialTemp, max($exponentialTemp))[0];
        $trendReport = [
            'coffee_type' => "The trend for coffee type for next month is **{$trendCoffee}**.",
            'temperature' => "The trend for temperature for next month is **{$trendTemp}**."
        ];

        // ---------------- TOP 10 BY LIKES ----------------
        $topByLikes = Coffee::withCount('likedBy')
            ->get()
            ->map(function ($c) {
                return [
                    'coffee_id' => $c->coffee_id,
                    'coffee_name' => $c->coffee_name,
                    'coffee_image' => $c->coffee_image ? asset('storage/' . $c->coffee_image) : null,
                    'total_likes' => intval($c->liked_by_count ?? 0) + intval($c->coffee_likes ?? 0)
                ];
            })
            ->sortByDesc('total_likes')
            ->take(10)
            ->values();

        $topLikedCoffee = $topByLikes->first(); // Top 1 liked coffee for report

        // ---------------- TOP 10 BY AVERAGE RATING ----------------
        $topByRating = Coffee::withAvg('ratings', 'rating')
            ->get()
            ->map(function ($c) {
                return [
                    'coffee_id' => $c->coffee_id,
                    'coffee_name' => $c->coffee_name,
                    'coffee_image' => $c->coffee_image ? asset('storage/' . $c->coffee_image) : null,
                    'avg_rate' => round(floatval($c->ratings_avg_rating ?? 0), 1)
                ];
            })
            ->sortByDesc('avg_rate')
            ->take(10)
            ->values();

        $userCount = User::count();
        $totalCoffees = Coffee::count();

        return response()->json([
            'monthlyData' => $monthlyData,
            'monthlyForecast' => $monthlyForecast,
            'monthlyMovingAvg' => $monthlyMovingAvg,
            'monthLabels' => $monthLabels,
            'currentYear' => $currentYear,
            'topByLikes' => $topByLikes,
            'topLikedCoffee' => $topLikedCoffee,
            'topByRating' => $topByRating,
            'userCount' => $userCount,
            'coffeeCount' => $totalCoffees,
            'trendReport' => $trendReport
        ]);
    }

    // ---------------- HELPER FUNCTIONS ----------------
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

        // First forecast = first actual
        $forecasts[0] = $series[0];

        // Compute forecast for each month
        for ($i = 1; $i < $n; $i++) {
            $forecasts[$i] = round($alpha * $series[$i] + (1 - $alpha) * $forecasts[$i - 1], 2);
        }

        // Forecast next month after last month
        $nextForecast = round($alpha * end($series) + (1 - $alpha) * end($forecasts), 2);

        return [
            'history' => $forecasts, // Sep-Dec forecasted history
            'next' => $nextForecast   // Jan forecast
        ];
    }
}
