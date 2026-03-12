<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormColumnConfig extends Model
{
    protected $fillable = [
        'scope',
        'visible_columns',
    ];

    protected $casts = [
        'visible_columns' => 'array',
    ];

    /**
     * Get config for a webform. Tries form-level first, then type-level (station:submission_form).
     */
    public static function forWebform(string $webformId, ?string $submissionForm, ?string $station): ?self
    {
        $formScope = 'form:' . $webformId;
        $config = static::where('scope', $formScope)->first();
        if ($config) {
            return $config;
        }

        if ($submissionForm && $station) {
            $typeScope = 'type:' . $station . ':' . $submissionForm;
            return static::where('scope', $typeScope)->first();
        }

        return null;
    }

    /**
     * Save config for a webform at form-level.
     */
    public static function saveForWebform(string $webformId, array $visibleColumns): self
    {
        $scope = 'form:' . $webformId;
        return static::updateOrCreate(
            ['scope' => $scope],
            ['visible_columns' => $visibleColumns]
        );
    }
}
