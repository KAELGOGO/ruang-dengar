<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['body', 'flagged'];

    protected function casts(): array
    {
        // Chat is sensitive: stored encrypted with APP_KEY, readable only through the app.
        return ['body' => 'encrypted', 'flagged' => 'boolean'];
    }

    public function counselingSession(): BelongsTo
    {
        return $this->belongsTo(CounselingSession::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
