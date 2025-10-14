<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_submission_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the contact submission that was read.
     */
    public function contactSubmission(): BelongsTo
    {
        return $this->belongsTo(ContactSubmission::class);
    }

    /**
     * Get the user who read the submission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
