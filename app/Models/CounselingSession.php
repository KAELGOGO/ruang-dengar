<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CounselingSession extends Model
{
    public const SCHEDULED = 'scheduled';
    public const ACTIVE = 'active';
    public const ENDED = 'ended';
    public const ESCALATED = 'escalated';
    public const CANCELLED = 'cancelled';

    // Services the user can pick ("select service" in the flowchart). Edit freely.
    public const TOPICS = [
        'cerita' => 'Butuh teman cerita',
        'akademik' => 'Tekanan akademik',
        'relasi' => 'Keluarga & hubungan',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = ['topic', 'scheduled_at'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'listener_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class, 'availability_slot_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function escalation(): HasOne
    {
        return $this->hasOne(Escalation::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function hasParticipant(User $user): bool
    {
        return $user->id === $this->user_id || $user->id === $this->listener_id;
    }
}
