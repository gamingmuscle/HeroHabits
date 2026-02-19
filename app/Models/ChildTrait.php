<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildTrait extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'child_traits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'child_id',
        'trait_id',
        'level',
        'experience_points',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'experience_points' => 'integer',
        ];
    }

    /**
     * Get the child that owns this trait progress.
     */
    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get the trait.
     */
    public function trait()
    {
        return $this->belongsTo(CharacterTrait::class, 'trait_id');
    }

    /**
     * Add experience points and level up if needed.
     */
    public function addExperience(int $amount): array
    {
        $this->experience_points += $amount;
        $levelsGained = 0;

        // Check for level ups (cumulative XP system)
        $nextLevelXP = CharacterTrait::experienceForLevel($this->level + 1);
        while ($nextLevelXP > 0 && $this->experience_points >= $nextLevelXP) {
            $this->level++;
            $levelsGained++;
            $nextLevelXP = CharacterTrait::experienceForLevel($this->level + 1);
        }

        $this->save();

        return [
            'leveled_up' => $levelsGained > 0,
            'levels_gained' => $levelsGained,
            'new_level' => $this->level,
            'trait_name' => $this->trait->name ?? 'Unknown',
        ];
    }

    /**
     * Get experience needed for next level.
     */
    public function experienceToNextLevel(): int
    {
        $nextLevelXP = CharacterTrait::experienceForLevel($this->level + 1);
        if ($nextLevelXP == 0) return 0; // Max level reached
        return max(0, $nextLevelXP - $this->experience_points);
    }

    /**
     * Get progress percentage to next level.
     */
    public function progressPercentage(): float
    {
        $currentLevelXP = CharacterTrait::experienceForLevel($this->level);
        $nextLevelXP = CharacterTrait::experienceForLevel($this->level + 1);

        if ($nextLevelXP == 0) return 100; // Max level

        $xpIntoCurrentLevel = $this->experience_points - $currentLevelXP;
        $xpNeededForLevel = $nextLevelXP - $currentLevelXP;

        if ($xpNeededForLevel == 0) return 0;
        return ($xpIntoCurrentLevel / $xpNeededForLevel) * 100;
    }
}
