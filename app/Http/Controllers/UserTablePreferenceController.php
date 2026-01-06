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
     * Supports hierarchy-aware category matching.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $category = $request->get('category');
        $preferenceName = $request->get('preference_name'); // e.g., 'list-view-columns' or 'detail-view-layout'

        $query = UserTablePreference::where('user_id', $userId);

        if ($preferenceName) {
            $query->where('preference_name', $preferenceName);
        }

        if ($category !== null) {
            // Support hierarchy: check exact match and parent levels
            $query->where(function ($q) use ($category) {
                // Exact match
                $q->where('category', $category);
                
                // Parent levels (for hierarchy inheritance)
                $parts = explode(':', $category);
                if (count($parts) >= 3) {
                    // Form-level: also check type-level and station-level
                    $typeCategory = $parts[0] . ':' . $parts[1];
                    $stationCategory = $parts[0];
                    $q->orWhere('category', $typeCategory)
                      ->orWhere('category', $stationCategory);
                } elseif (count($parts) === 2) {
                    // Type-level: also check station-level
                    $stationCategory = $parts[0];
                    $q->orWhere('category', $stationCategory);
                }
                
                // Global preferences (null category)
                $q->orWhereNull('category');
            });
        } else {
            // If no category specified, include all preferences
            $query->orWhereNull('category');
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
     * Get inherited preferences for a given category hierarchy.
     * Returns merged preferences from form-level → type-level → station-level → global.
     */
    public function getInheritedPreferences(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $category = $request->get('category'); // e.g., 'regenbogen:Contact Form:form_id_123'
        $preferenceName = $request->get('preference_name', 'list-view-columns');

        if (!$category) {
            return response()->json([
                'success' => true,
                'preferences' => null,
                'inherited_from' => null
            ]);
        }

        $parts = explode(':', $category);
        $mergedPreferences = null;
        $inheritedFrom = null;

        // Check in order: form-level → type-level → station-level → global
        $levels = [];
        if (count($parts) >= 3) {
            $levels[] = $category; // Form-level
            $levels[] = $parts[0] . ':' . $parts[1]; // Type-level
            $levels[] = $parts[0]; // Station-level
        } elseif (count($parts) === 2) {
            $levels[] = $category; // Type-level
            $levels[] = $parts[0]; // Station-level
        } elseif (count($parts) === 1) {
            $levels[] = $category; // Station-level
        }
        $levels[] = null; // Global

        foreach ($levels as $level) {
            $preference = UserTablePreference::where('user_id', $userId)
                ->where('preference_name', $preferenceName)
                ->where('category', $level)
                ->where('is_default', true)
                ->first();

            if ($preference) {
                $mergedPreferences = $preference;
                $inheritedFrom = $level ?? 'global';
                break; // Use the most specific preference found
            }
        }

        // If no default found, get the most recent one
        if (!$mergedPreferences) {
            foreach ($levels as $level) {
                $preference = UserTablePreference::where('user_id', $userId)
                    ->where('preference_name', $preferenceName)
                    ->where('category', $level)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($preference) {
                    $mergedPreferences = $preference;
                    $inheritedFrom = $level ?? 'global';
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'preference' => $mergedPreferences,
            'inherited_from' => $inheritedFrom
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
        $category = $validated['category'] ?? null;
        $preferenceName = $validated['preference_name'];
        $isDefault = $request->get('is_default', false);

        // Ensure only ONE preference exists per category/name combination
        // Delete any existing preferences for this category/name (single preference system)
        UserTablePreference::where('user_id', $userId)
            ->where('category', $category)
            ->where('preference_name', $preferenceName)
            ->delete();

        // Create the single preference (always create new to ensure clean state)
        $preference = UserTablePreference::create([
            'user_id' => $userId,
            ...$validated
        ]);

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
