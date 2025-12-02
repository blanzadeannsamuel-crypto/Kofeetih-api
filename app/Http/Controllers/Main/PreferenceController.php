<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\Main\Preference;
use Illuminate\Support\Facades\Cache;

class PreferenceController extends Controller
{
    use AuthorizesRequests;

    private function rules(){
        return [
            'coffee_type' => 'nullable|string',
            'coffee_allowance' => 'nullable|integer|min:120',
            'temp' => 'nullable|in:hot,cold',
            'lactose' => 'nullable|boolean',
            'nuts_allergy' => 'nullable|boolean',
        ];
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $cacheKey = "preference_user_{$userId}";

        $preference = Cache::remember($cacheKey, 3600, function() use ($userId) {
            return Preference::firstOrCreate(['user_id' => $userId]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $preference,
        ]);
    }

    public function store(Request $request)
    {

        $validated = $request->validate($this->rules());
        $userId = $request->user()->id;

        $validated['user_id'] = $userId;

        $preference = Preference::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        Cache::forget("preference_user_{$userId}");

        return response()->json([
            'status' => 'success',
            'data' => $preference,
            'message' => 'Preferences saved.',
        ]);
    }

    public function show(Preference $preference)
    {
        $this->authorize('view', $preference);

        return response()->json([
            'status' => 'success',
            'data' => $preference,
        ]);
    }

    public function update(Request $request, Preference $preference)
    {
        $this->authorize('update', $preference);

        $validated = $request->validate($this->rules());

        $preference->update($validated);

        Cache::forget("preference_user_{$preference->user_id}");

        return response()->json([
            'status' => 'success',
            'data' => $preference,
            'message' => 'Preference updated.',
        ]);
    }

    public function destroy(Preference $preference)
    {
        $this->authorize('delete', $preference);

        $preference->delete();

        Cache::forget("preference_user_{$preference->user_id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Preference removed.',
        ]);
    }

    public function restore(int $id)
    {
        $preference = Preference::onlyTrashed()->find($id);

        if (!$preference) {
            return response()->json([
                'status' => 'error',
                'message' => 'Preference not found or not deleted.',
            ], 404);
        }

        $this->authorize('restore', $preference);

        $preference->restore();

        Cache::forget("preference_user_{$preference->user_id}");

        return response()->json([
            'status' => 'success',
            'message' => 'Preference restored successfully.',
            'data' => $preference,
        ]);
    }
}
