<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestCompletion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quest_id',
        'child_id',
        'completion_date',
        'gold_earned',
        'status',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'gold_earned' => 'integer',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the quest that this completion belongs to.
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Get the child that completed this quest.
     */
    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get the user who approved this completion.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending completions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope a query to only include accepted completions.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'Accepted');
    }

    /**
     * Scope a query to only include denied completions.
     */
    public function scopeDenied($query)
    {
        return $query->where('status', 'Denied');
    }

    /**
     * Accept this quest completion.
     * Awards gold and experience points.
     */
    public function accept(User $approver): array
    {
        // Calculate XP: gold_reward * 10
        $experienceAwarded = $this->gold_earned * 10;

        $this->update([
            'status' => 'Accepted',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'experience_points_awarded' => $experienceAwarded,
        ]);

        // Add gold to child's balance
        $this->child->addGold($this->gold_earned);

        // Add XP to child's overall level
        $childLevelUp = $this->child->addExperience($experienceAwarded);

        // Award XP to traits
        $traitLevelUps = $this->awardTraitExperience($experienceAwarded);

        return [
            'child_level_up' => $childLevelUp,
            'trait_level_ups' => $traitLevelUps,
        ];
    }

    /**
     * Award experience to traits tagged on this quest.
     */
    protected function awardTraitExperience(int $totalXp): array
    {
        $traits = $this->quest->traits;
        $levelUps = [];

        if ($traits->isEmpty()) {
            return $levelUps;
        }

        // Split XP equally among all tagged traits
        $xpPerTrait = intval($totalXp / $traits->count());

        foreach ($traits as $trait) {
            // Get or create child_trait record
            $childTrait = ChildTrait::firstOrCreate([
                'child_id' => $this->child_id,
                'trait_id' => $trait->id,
            ], [
                'level' => 1,
                'experience_points' => 0,
            ]);

            // Add XP and check for level up
            $result = $childTrait->addExperience($xpPerTrait);

            if ($result['leveled_up']) {
                $levelUps[] = $result;
            }
        }

        return $levelUps;
    }

    /**
     * Deny this quest completion.
     */
    public function deny(User $approver): void
    {
        $this->update([
            'status' => 'Denied',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Check if this completion is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if this completion is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'Accepted';
    }

    /**
     * Check if this completion is denied.
     */
    public function isDenied(): bool
    {
        return $this->status === 'Denied';
    }
}
