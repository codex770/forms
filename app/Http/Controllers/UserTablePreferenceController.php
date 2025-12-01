<?php

namespace App\Http\Controllers;

use App\Models\UserTablePreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTablePreferenceController extends Controller
{
    /**
     * Get all preferences for the current user (optionally filtered by category).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $category = $request->get('category');

        $query = UserTablePreference::where('user_id', $userId);

        if ($category !== null) {
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                  ->orWhereNull('category'); // Include global preferences
            });
        }

        $preferences = $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'preferences' => $preferences
        ]);
    }

    /**
     * Get a specific preference by ID.
     */
    public function show(UserTablePreference $preference): JsonResponse
    {
        // Ensure user can only access their own preferences
        if ($preference->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'preference' => $preference
        ]);
    }

    /**
     * Save or update a preference.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:255',
            'preference_name' => 'required|string|max:255',
            'visible_columns' => 'nullable|array',
            'sort_config' => 'nullable|array',
            'saved_filters' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        $userId = auth()->id();

        // If setting as default, unset other defaults for this user/category
        if ($request->get('is_default', false)) {
            UserTablePreference::where('user_id', $userId)
                ->where('category', $validated['category'] ?? null)
                ->update(['is_default' => false]);
        }

        // Check if preference already exists
        $preference = UserTablePreference::where('user_id', $userId)
            ->where('category', $validated['category'] ?? null)
            ->where('preference_name', $validated['preference_name'])
            ->first();

        if ($preference) {
            // Update existing
            $preference->update($validated);
        } else {
            // Create new
            $preference = UserTablePreference::create([
                'user_id' => $userId,
                ...$validated
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preference saved successfully',
            'preference' => $preference
        ], 201);
    }

    /**
     * Update a preference.
     */
    public function update(Request $request, UserTablePreference $preference): JsonResponse
    {
        // Ensure user can only update their own preferences
        if ($preference->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'preference_name' => 'sometimes|string|max:255',
            'visible_columns' => 'nullable|array',
            'sort_config' => 'nullable|array',
            'saved_filters' => 'nullable|array',
            'is_default' => 'nullable|boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->get('is_default', false)) {
            UserTablePreference::where('user_id', auth()->id())
                ->where('category', $preference->category)
                ->where('id', '!=', $preference->id)
                ->update(['is_default' => false]);
        }

        $preference->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Preference updated successfully',
            'preference' => $preference
        ]);
    }

    /**
     * Delete a preference.
     */
    public function destroy(UserTablePreference $preference): JsonResponse
    {
        // Ensure user can only delete their own preferences
        if ($preference->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $preference->delete();

        return response()->json([
            'success' => true,
            'message' => 'Preference deleted successfully'
        ]);
    }

    /**
     * Load a preference (apply it to current view).
     */
    public function load(UserTablePreference $preference): JsonResponse
    {
        // Ensure user can only load their own preferences
        if ($preference->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'preference' => $preference
        ]);
    }
}
