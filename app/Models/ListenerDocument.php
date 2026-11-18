<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListenerDocument extends Model
{
    protected $fillable = ['path', 'original_name'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ListenerProfile::class, 'listener_profile_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
