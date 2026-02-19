<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
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
        'gold_reward',
        'max_turnins',
        'turnin_period',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gold_reward' => 'integer',
            'max_turnins' => 'integer',
            'turnin_period' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent user that owns the quest.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all completions for this quest.
     */
    public function completions()
    {
        return $this->hasMany(QuestCompletion::class);
    }

    /**
     * Get pending completions for this quest.
     */
    public function pendingCompletions()
    {
        return $this->hasMany(QuestCompletion::class)->where('status', 'Pending');
    }

    /**
     * Get all traits tagged to this quest.
     */
    public function traits()
    {
        return $this->belongsToMany(CharacterTrait::class, 'quest_traits', 'quest_id', 'trait_id');
    }

    /**
     * Scope a query to only include active quests.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive quests.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Toggle the active status of the quest.
     */
    public function toggleActive(): void
    {
        $this->update(['is_active' => !$this->is_active]);
    }

    /**
     * Check if a child can complete this quest on a given date.
     */
    public function canBeCompletedBy(Child $child, string $date): bool
    {
        // Check if already completed on this date
        return !$this->completions()
            ->where('child_id', $child->id)
            ->where('completion_date', $date)
            ->exists();
    }
}
