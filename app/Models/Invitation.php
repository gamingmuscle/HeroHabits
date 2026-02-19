<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'expires_at',
        'used_by',
        'used_at',
        'created_by_email',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Get the user who used this invitation.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * Check if the invitation is still valid.
     */
    public function isValid(): bool
    {
        // Already used
        if ($this->used_at !== null) {
            return false;
        }

        // Check expiration
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark the invitation as used.
     */
    public function markAsUsed(User $user): void
    {
        $this->update([
            'used_by' => $user->id,
            'used_at' => now(),
        ]);
    }

    /**
     * Generate a new invitation code.
     */
    public static function generate(?int $expiryDays = null): self
    {
        $codeLength = config('herohabits.registration.invitation_code_length', 8);
        $expiryDays = $expiryDays ?? config('herohabits.registration.invitation_expiry_days', 30);

        // Generate unique code
        do {
            $code = strtoupper(Str::random($codeLength));
        } while (self::where('code', $code)->exists());

        return self::create([
            'code' => $code,
            'expires_at' => $expiryDays > 0 ? now()->addDays($expiryDays) : null,
        ]);
    }

    /**
     * Scope to get only valid invitations.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get used invitations.
     */
    public function scopeUsed($query)
    {
        return $query->whereNotNull('used_at');
    }

    /**
     * Scope to get expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('used_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
