<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListenerProfile extends Model
{
    public const VOLUNTEER = 'volunteer';
    public const PSYCH_STUDENT = 'psych_student';
    public const PSYCHOLOGIST = 'psychologist';

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    // Labels shown in the UI ("listener title" in the proposal).
    public const TITLES = [
        self::VOLUNTEER => 'Relawan terverifikasi',
        self::PSYCH_STUDENT => 'Mahasiswa psikologi',
        self::PSYCHOLOGIST => 'Psikolog',
    ];

    // credential_level and verification_status are set by the admin only.
    protected $fillable = ['bio'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ListenerDocument::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
