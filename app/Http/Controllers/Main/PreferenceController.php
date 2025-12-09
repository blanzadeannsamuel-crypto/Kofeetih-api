<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Main\Preference;
use Illuminate\Support\Facades\Cache;
use App\Models\AuditLogsModel;
use App\Models\AuditLogsReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PreferenceController extends Controller
{
    use AuthorizesRequests;
    private function rules(): array
    {
        return [
            'coffee_type' => 'nullable|in:arabica,robusta,liberica',
            'coffee_allowance' => 'nullable|integer|min:120',
            'serving_temp' => 'nullable|in:hot,iced,both',
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
        $user = $request->user();
        $validated['user_id'] = $user->id;

        $preference = Preference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        // Audit log for adding/updating preference
        AuditLogsReport::create([
            'user_id' => $user->id,
            'type' => 'interaction',
            'action' => 'PREFERENCE_UPDATED',
            'description' => "{$user->display_name} added/updated their preference"
        ]);

        Cache::forget("preference_user_{$user->id}");

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

        $user = $request->user();

        AuditLogsReport::create([
            'user_id' => $preference->user_id,
            'type' => 'interaction',
            'action' => 'PREFERENCE_UPDATED',
            'description' => "{$user->display_name} updated their preference"
        ]);

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
        $preference = Preference::onlyTrashed()->findOrFail($id);

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
