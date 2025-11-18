<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\Main\Preference;

class PreferenceController extends Controller
{
    use AuthorizesRequests;


    public function index(Request $request)
    {
        $user = $request->user();
        $preference = Preference::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'message' => $preference,
        ], 201);
    }

    public function create(Request $request)
    {
       
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $validate = $request->validate([
            'coffee_type' => 'nullable|string',
            'coffee_allowance' => 'nullable|integer|min:120',
            'temp' => 'nullable|in:hot,cold',
            'lactose' => 'boolean',
            'nuts_allergey' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        $preference = Preference::updateOrCreate(
            ['user_id' => $validate['user_id']],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'data' => $preference,
            'message' => 'Preferences saved.'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Preference $preference)
    {
        $this->authorize('view', $preference);

        return response()->json([
            'data' => $preference
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Preference $preference)
    {
        $this->authorize('update', $preference);

        $validated = $request->validate([
            'coffee_type' => 'nullable|string',
            'coffee_allowance' => 'nullable|integer|min:120',
            'temp' => 'nullable|in:hot,cold',
            'lactose' => 'boolean',
            'nuts_allergey' => 'boolean',
        ]);

        $preference->update($validated);

        return response()->json([
            'data' => $preference,
            'message' => 'Preference Updated'
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Preference $preference)
    {
        $this->authorize('delete', $preference);

        $preference->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'preference removed.'
        ], 200);
    }
}
