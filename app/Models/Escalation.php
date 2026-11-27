<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escalation extends Model
{
    public const OPEN = 'open';
    public const ASSIGNED = 'assigned';
    public const RESOLVED = 'resolved';

    protected $fillable = ['source', 'reason'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function counselingSession(): BelongsTo
    {
        return $this->belongsTo(CounselingSession::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'psychologist_id');
    }

    // Module 4: labels for the queue.
    public const SOURCES = [
        'listener' => 'Listener',
        'keyword' => 'Kata kunci',
        'user' => 'Pengguna',
    ];

    /**
     * Module 4: the new session the psychologist has with the user for this case.
     * No column links them, so find the first session of that pair opened after the escalation.
     */
    public function followUpSession(): ?CounselingSession
    {
        if (! $this->psychologist_id) {
            return null;
        }

        return CounselingSession::where('user_id', $this->counselingSession->user_id)
            ->where('listener_id', $this->psychologist_id)
            ->where('id', '>', $this->counseling_session_id)
            ->where('created_at', '>=', $this->created_at)
            ->orderBy('id')
            ->first();
    }
}
