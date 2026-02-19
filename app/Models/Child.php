<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Child extends Authenticatable
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'age',
        'avatar_image',
        'pin',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'gold_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'gold_balance' => 'integer',
            'level' => 'integer',
            'experience_points' => 'integer',
        ];
    }

    /**
     * Set the PIN attribute with hashing.
     */
    public function setPinAttribute($value): void
    {
        $this->attributes['pin'] = \Hash::make($value);
    }

    /**
     * Verify a PIN against the stored hash.
     */
    public function verifyPin(string $pin): bool
    {
        return \Hash::check($pin, $this->pin);
    }

    /**
     * Get the parent user that owns the child.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all quest completions for this child.
     */
    public function questCompletions()
    {
        return $this->hasMany(QuestCompletion::class);
    }

    /**
     * Get pending quest completions for this child.
     */
    public function pendingCompletions()
    {
        return $this->hasMany(QuestCompletion::class)->where('status', 'Pending');
    }

    /**
     * Get accepted quest completions for this child.
     */
    public function acceptedCompletions()
    {
        return $this->hasMany(QuestCompletion::class)->where('status', 'Accepted');
    }

    /**
     * Get all treasure purchases for this child.
     */
    public function treasurePurchases()
    {
        return $this->hasMany(TreasurePurchase::class);
    }

    /**
     * Get all traits for this child with their levels.
     */
    public function traits()
    {
        return $this->belongsToMany(CharacterTrait::class, 'child_traits', 'child_id', 'trait_id')
            ->withPivot('level', 'experience_points')
            ->withTimestamps();
    }

    /**
     * Get child traits (pivot records).
     */
    public function childTraits()
    {
        return $this->hasMany(ChildTrait::class);
    }

    /**
     * Add gold to this child's balance.
     */
    public function addGold(int $amount): void
    {
        $this->increment('gold_balance', $amount);
    }

    /**
     * Subtract gold from this child's balance.
     */
    public function subtractGold(int $amount): bool
    {
        if ($this->gold_balance >= $amount) {
            $this->decrement('gold_balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Check if child has enough gold.
     */
    public function hasEnoughGold(int $amount): bool
    {
        return $this->gold_balance >= $amount;
    }

    /**
     * Get cumulative experience required to reach a specific level.
     */
    public static function experienceForLevel(int $level): int
    {
        return CharacterLevel::experienceForLevel($level);
    }

    /**
     * Add experience points and level up if needed.
     * Returns information about level ups.
     */
    public function addExperience(int $amount): array
    {
        $this->experience_points += $amount;
        $levelsGained = 0;
        $oldLevel = $this->level;

        // Check for level ups (cumulative XP system)
        $nextLevelXP = self::experienceForLevel($this->level + 1);
        while ($nextLevelXP > 0 && $this->experience_points >= $nextLevelXP) {
            $this->level++;
            $levelsGained++;
            $nextLevelXP = self::experienceForLevel($this->level + 1);
        }

        $this->save();

        return [
            'leveled_up' => $levelsGained > 0,
            'levels_gained' => $levelsGained,
            'old_level' => $oldLevel,
            'new_level' => $this->level,
            'current_xp' => $this->experience_points,
            'xp_to_next_level' => $this->experienceToNextLevel(),
        ];
    }

    /**
     * Get experience needed for next level.
     */
    public function experienceToNextLevel(): int
    {
        $nextLevelXP = self::experienceForLevel($this->level + 1);
        if ($nextLevelXP == 0) return 0; // Max level reached
        return max(0, $nextLevelXP - $this->experience_points);
    }

    /**
     * Get progress percentage to next level.
     */
    public function progressPercentage(): float
    {
        $currentLevelXP = self::experienceForLevel($this->level);
        $nextLevelXP = self::experienceForLevel($this->level + 1);

        if ($nextLevelXP == 0) return 100; // Max level

        $xpIntoCurrentLevel = $this->experience_points - $currentLevelXP;
        $xpNeededForLevel = $nextLevelXP - $currentLevelXP;

        if ($xpNeededForLevel == 0) return 0;
        return ($xpIntoCurrentLevel / $xpNeededForLevel) * 100;
    }

    /**
     * Initialize all character traits for this child at level 1.
     */
    public function setupCharacterTraits(): void
    {
        $traits = CharacterTrait::all();

        foreach ($traits as $trait) {
            // Create if doesn't exist
            ChildTrait::firstOrCreate([
                'child_id' => $this->id,
                'trait_id' => $trait->id,
            ], [
                'level' => 1,
                'experience_points' => 0,
            ]);
        }
    }

    /**
     * Check for and apply any pending level-ups based on current XP.
     * Returns information about level ups.
     */
    public function checkAndLevelUp(): array
    {
        $levelsGained = 0;
        $oldLevel = $this->level;

        // Check for level ups (cumulative XP system)
        $nextLevelXP = self::experienceForLevel($this->level + 1);
        while ($nextLevelXP > 0 && $this->experience_points >= $nextLevelXP) {
            $this->level++;
            $levelsGained++;
            $nextLevelXP = self::experienceForLevel($this->level + 1);
        }

        if ($levelsGained > 0) {
            $this->save();
        }

        // Also check traits for auto-leveling
        $this->checkAndLevelUpTraits();

        return [
            'leveled_up' => $levelsGained > 0,
            'levels_gained' => $levelsGained,
            'old_level' => $oldLevel,
            'new_level' => $this->level,
            'current_xp' => $this->experience_points,
            'xp_to_next_level' => $this->experienceToNextLevel(),
        ];
    }

    /**
     * Check and level up all traits that have enough XP.
     */
    public function checkAndLevelUpTraits(): void
    {
        $childTraits = $this->childTraits;

        foreach ($childTraits as $childTrait) {
            $levelsGained = 0;

            $nextLevelXP = CharacterTrait::experienceForLevel($childTrait->level + 1);
            while ($nextLevelXP > 0 && $childTrait->experience_points >= $nextLevelXP) {
                $childTrait->level++;
                $levelsGained++;
                $nextLevelXP = CharacterTrait::experienceForLevel($childTrait->level + 1);
            }

            if ($levelsGained > 0) {
                $childTrait->save();
            }
        }
    }
}
