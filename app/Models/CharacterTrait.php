<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterTrait extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'traits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get all quests tagged with this trait.
     */
    public function quests()
    {
        return $this->belongsToMany(Quest::class, 'quest_traits');
    }

    /**
     * Get all children who have this trait.
     */
    public function children()
    {
        return $this->belongsToMany(Child::class, 'child_traits')
            ->withPivot('level', 'experience_points')
            ->withTimestamps();
    }

    /**
     * Get cumulative experience required to reach a specific trait level.
     */
    public static function experienceForLevel(int $level): int
    {
        return CharacterLevel::experienceForLevel($level);
    }
}
