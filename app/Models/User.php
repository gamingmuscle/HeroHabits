<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'displayname',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get all children for this parent user.
     */
    public function children()
    {
        return $this->hasMany(Child::class);
    }

    /**
     * Get all quests created by this parent user.
     */
    public function quests()
    {
        return $this->hasMany(Quest::class);
    }

    /**
     * Get all treasures created by this parent user.
     */
    public function treasures()
    {
        return $this->hasMany(Treasure::class);
    }

    /**
     * Get quest completions approved by this user.
     */
    public function approvedCompletions()
    {
        return $this->hasMany(QuestCompletion::class, 'approved_by');
    }

    /**
     * Get quest turnin periods for this user.
     */
    public function questTurninPeriods()
    {
        return $this->hasMany(QuestTurninPeriod::class);
    }
}
