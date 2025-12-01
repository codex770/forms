<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTablePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'preference_name',
        'visible_columns',
        'sort_config',
        'saved_filters',
        'is_default',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'sort_config' => 'array',
        'saved_filters' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
