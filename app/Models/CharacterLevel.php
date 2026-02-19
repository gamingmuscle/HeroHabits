<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CharacterLevel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'level',
        'experience_required',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'experience_required' => 'integer',
        ];
    }

    /**
     * Get experience required for a specific level (cached).
     */
    public static function experienceForLevel(int $level): int
    {
        return Cache::remember("character_level_{$level}", 3600, function () use ($level) {
            $levelData = self::where('level', $level)->first();
            return $levelData ? $levelData->experience_required : 0;
        });
    }

    /**
     * Get all levels as a cached collection.
     */
    public static function getAllLevels()
    {
        return Cache::remember('character_levels_all', 3600, function () {
            return self::orderBy('level')->get();
        });
    }

    /**
     * Get the next level data for a given level.
     */
    public static function getNextLevel(int $currentLevel)
    {
        return self::where('level', $currentLevel + 1)->first();
    }
}
