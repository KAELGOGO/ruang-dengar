<?php

namespace App\Models;

use App\Support\AgeTier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_LISTENER = 'listener';
    public const ROLE_ADMIN = 'admin';

    // role is not mass assignable: registration sets it explicitly, so nobody can post role=admin.
    protected $fillable = [
        'name',
        'email',
        'password',
        'birth_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'onboarded_at' => 'datetime',
        ];
    }

    public function listenerProfile(): HasOne
    {
        return $this->hasOne(ListenerProfile::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class, 'listener_id');
    }

    public function sessionsAsUser(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'user_id');
    }

    public function sessionsAsListener(): HasMany
    {
        return $this->hasMany(CounselingSession::class, 'listener_id');
    }

    public function ageTier(): ?string
    {
        return $this->birth_date ? AgeTier::of($this->birth_date) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isListener(): bool
    {
        return $this->role === self::ROLE_LISTENER;
    }

    public function isVerifiedListener(): bool
    {
        return $this->isListener() && $this->listenerProfile?->verification_status === ListenerProfile::APPROVED;
    }

    public function isPsychologist(): bool
    {
        return $this->isVerifiedListener() && $this->listenerProfile->credential_level === ListenerProfile::PSYCHOLOGIST;
    }

    /** Name Dengi uses: the onboarding nickname, else the name without the demo "(20)" suffix. */
    public function displayName(): string
    {
        return $this->nickname ?: Str::before($this->name, ' (');
    }

    public function needsOnboarding(): bool
    {
        return $this->role === self::ROLE_USER && $this->onboarded_at === null;
    }
}
