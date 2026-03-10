<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_submission_id',
        'user_id',
        'marked_at',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    public function contactSubmission(): BelongsTo
    {
        return $this->belongsTo(ContactSubmission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

