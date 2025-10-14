<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'data',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get all read records for this submission.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ContactRead::class);
    }

    /**
     * Get reads with user information, ordered by read time.
     */
    public function readsWithUsers(): HasMany
    {
        return $this->hasMany(ContactRead::class)
            ->with('user:id,name')
            ->orderBy('read_at');
    }

    /**
     * Check if a specific user has read this submission.
     */
    public function isReadByUser(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Mark as read by a specific user.
     */
    public function markAsReadBy(int $userId): ContactRead
    {
        return $this->reads()->firstOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * Get the count of users who have read this submission.
     */
    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get unread submissions for a specific user.
     */
    public function scopeUnreadByUser($query, int $userId)
    {
        return $query->whereDoesntHave('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope to get read submissions for a specific user.
     */
    public function scopeReadByUser($query, int $userId)
    {
        return $query->whereHas('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
