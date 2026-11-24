<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AvailabilitySlot extends Model
{
    // Allowed slot length in minutes (Module 2).
    public const MIN_MINUTES = 30;

    public const MAX_MINUTES = 120;

    protected $fillable = ['starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'listener_id');
    }

    public function counselingSession(): HasOne
    {
        return $this->hasOne(CounselingSession::class);
    }

    /** Slots that are not over yet, earliest first. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('ends_at', '>', now())->orderBy('starts_at');
    }

    /** Slots that share any minute with [$starts, $ends); touching edges do not overlap. */
    public function scopeOverlapping(Builder $query, CarbonInterface $starts, CarbonInterface $ends): void
    {
        $query->where('starts_at', '<', $ends)->where('ends_at', '>', $starts);
    }
}
