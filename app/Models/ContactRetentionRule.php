<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRetentionRule extends Model
{
    protected $fillable = [
        'webform_id',
        'retention_days',
        'notes',
    ];

    protected $casts = [
        'retention_days' => 'integer',
    ];

    /**
     * Get the effective retention days for a given webform_id.
     * Looks for a form-specific rule first, then falls back to the global rule.
     * Returns null if no rule is configured (keep forever).
     */
    public static function effectiveDaysFor(string $webformId): ?int
    {
        $specific = static::where('webform_id', $webformId)->first();
        if ($specific) {
            return $specific->retention_days; // may be null = keep forever
        }

        $global = static::whereNull('webform_id')->first();
        return $global?->retention_days; // null if no global rule
    }
}
