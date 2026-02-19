<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasurePurchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'treasure_id',
        'child_id',
        'gold_spent',
        'purchased_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gold_spent' => 'integer',
            'purchased_at' => 'datetime',
        ];
    }

    /**
     * Get the treasure that was purchased.
     */
    public function treasure()
    {
        return $this->belongsTo(Treasure::class);
    }

    /**
     * Get the child who purchased the treasure.
     */
    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Scope a query to get recent purchases.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('purchased_at', 'desc')->limit($limit);
    }

    /**
     * Scope a query to get purchases for a specific child.
     */
    public function scopeForChild($query, int $childId)
    {
        return $query->where('child_id', $childId);
    }

    /**
     * Scope a query to get purchases within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('purchased_at', [$startDate, $endDate]);
    }
}
