<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = ['score', 'comment'];

    public function counselingSession(): BelongsTo
    {
        return $this->belongsTo(CounselingSession::class);
    }
}
