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
}
