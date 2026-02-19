<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treasure extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'gold_cost',
        'is_available',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gold_cost' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /**
     * Get the parent user that owns the treasure.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all purchases of this treasure.
     */
    public function purchases()
    {
        return $this->hasMany(TreasurePurchase::class);
    }

    /**
     * Scope a query to only include available treasures.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include unavailable treasures.
     */
    public function scopeUnavailable($query)
    {
        return $query->where('is_available', false);
    }

    /**
     * Toggle the availability of the treasure.
     */
    public function toggleAvailability(): void
    {
        $this->update(['is_available' => !$this->is_available]);
    }

    /**
     * Purchase this treasure for a child.
     */
    public function purchaseFor(Child $child): bool
    {
        // Check if child has enough gold
        if (!$child->hasEnoughGold($this->gold_cost)) {
            return false;
        }

        // Subtract gold from child
        if (!$child->subtractGold($this->gold_cost)) {
            return false;
        }

        // Record the purchase
        $this->purchases()->create([
            'child_id' => $child->id,
            'gold_spent' => $this->gold_cost,
        ]);

        return true;
    }
}
