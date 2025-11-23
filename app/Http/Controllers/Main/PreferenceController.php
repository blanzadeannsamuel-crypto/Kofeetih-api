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

    public function index(Request $request)
    {
        $user = $request->user();

        $preference = Cache::remember("preference_user_{$user->id}", 3600, function () use ($user) {
            return Preference::firstOrCreate(['user_id' => $user->id]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $preference,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coffee_type' => 'nullable|string',
            'coffee_allowance' => 'nullable|integer|min:120',
            'temp' => 'nullable|in:hot,cold',
            'lactose' => 'nullable|boolean',
            'nuts_allergy' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        $preference = Preference::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        Cache::forget("preference_user_{$validated['user_id']}");

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

        $validated = $request->validate([
            'coffee_type' => 'nullable|string',
            'coffee_allowance' => 'nullable|integer|min:120',
            'temp' => 'nullable|in:hot,cold',
            'lactose' => 'nullable|boolean',
            'nuts_allergy' => 'nullable|boolean',
        ]);

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
            'message' => 'Preference removed (soft deleted).',
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
