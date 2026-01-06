<?php

namespace App\Helpers;

use App\Models\ContactSubmission;

/**
 * Helper class for determining smart defaults for form field preferences
 */
class FormDefaultsHelper
{
    /**
     * Get smart defaults for a form type and view type
     * 
     * Priority:
     * 1. Type-specific config defaults
     * 2. Field frequency analysis (fields appearing in >80% of forms)
     * 3. Common field patterns
     */
    public static function getDefaultsForType(
        ?string $submissionForm,
        string $viewType = 'list',
        ?string $station = null
    ): array {
        // 1. Check type-specific config defaults
        if ($submissionForm) {
            $configDefaults = config("form_type_defaults.{$submissionForm}.{$viewType}");
            if ($configDefaults) {
                return $configDefaults;
            }
        }
        
        // 2. Analyze field frequency if we have station and type
        if ($submissionForm && $station) {
            $frequencyDefaults = self::getDefaultsFromFrequency($submissionForm, $station, $viewType);
            if (!empty($frequencyDefaults)) {
                return $frequencyDefaults;
            }
        }
        
        // 3. Fallback to common defaults
        return config("form_type_defaults.default.{$viewType}", [
            'fname', 'lname', 'email', 'message_long'
        ]);
    }
    
    /**
     * Analyze field frequency across all forms of a type
     * Returns fields that appear in >80% of forms
     */
    private static function getDefaultsFromFrequency(
        string $submissionForm,
        string $station,
        string $viewType
    ): array {
        // Get all forms of this type
        $submissions = ContactSubmission::where('submission_form', $submissionForm)
            ->where('station', $station)
            ->limit(100) // Sample size
            ->get();
        
        if ($submissions->isEmpty()) {
            return [];
        }
        
        $fieldCounts = [];
        $totalForms = 0;
        $formFields = [];
        
        // Count field occurrences across forms
        foreach ($submissions as $submission) {
            $data = $submission->data ?? [];
            if (empty($data)) {
                continue;
            }
            
            $formFields[$submission->webform_id] = array_keys($data);
            $totalForms++;
            
            foreach (array_keys($data) as $field) {
                // Skip system fields
                if (in_array($field, ['webform_id', 'submission_form', 'station', 'category'])) {
                    continue;
                }
                
                if (!isset($fieldCounts[$field])) {
                    $fieldCounts[$field] = 0;
                }
                $fieldCounts[$field]++;
            }
        }
        
        if ($totalForms === 0) {
            return [];
        }
        
        // Calculate frequency and get fields appearing in >80% of forms
        $threshold = $totalForms * 0.8;
        $commonFields = [];
        
        foreach ($fieldCounts as $field => $count) {
            if ($count >= $threshold) {
                $commonFields[] = $field;
            }
        }
        
        // Sort by frequency (most common first)
        usort($commonFields, function ($a, $b) use ($fieldCounts) {
            return $fieldCounts[$b] <=> $fieldCounts[$a];
        });
        
        // Return top 5-8 most common fields (reasonable default)
        return array_slice($commonFields, 0, 8);
    }
    
    /**
     * Get default fields for a specific form (webform_id)
     * Returns first 4 fields from the form's JSON data
     * Falls back to type-level defaults if form has no data yet
     */
    public static function getDefaultsForForm(
        string $webformId,
        ?string $submissionForm = null,
        ?string $station = null,
        string $viewType = 'list'
    ): array {
        // Get first submission from this form to extract field order
        $submission = ContactSubmission::where('webform_id', $webformId)
            ->whereNotNull('data')
            ->first();
        
        if ($submission && $submission->data && is_array($submission->data)) {
            // Get all field keys from the JSON data
            $fieldKeys = array_keys($submission->data);
            
            // Filter out system fields
            $systemFields = ['webform_id', 'submission_form', 'station', 'category'];
            $dataFields = array_filter($fieldKeys, function($key) use ($systemFields) {
                return !in_array($key, $systemFields);
            });
            
            // Return first 4 fields (maintain order from JSON)
            $firstFour = array_slice(array_values($dataFields), 0, 4);
            
            if (!empty($firstFour)) {
                return $firstFour;
            }
        }
        
        // Form has no data yet - use type defaults if available
        if ($submissionForm && $station) {
            return self::getDefaultsForType($submissionForm, $viewType, $station);
        }
        
        // Ultimate fallback
        return config("form_type_defaults.default.{$viewType}", [
            'fname', 'lname', 'email', 'message_long'
        ]);
    }
}

