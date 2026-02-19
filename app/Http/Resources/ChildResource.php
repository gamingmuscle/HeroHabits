<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'avatar_image' => $this->avatar_image,
            'gold_balance' => $this->gold_balance,
            'created_at' => $this->created_at?->toIso8601String(),

            // PIN is always hidden via model's $hidden property

            // Conditional attributes
            'completed_quests' => $this->when(isset($this->completed_quests), $this->completed_quests ?? 0),
            'pending_approvals' => $this->when(isset($this->pending_approvals), $this->pending_approvals ?? 0),
            'completed_today' => $this->when(isset($this->completed_today), $this->completed_today ?? 0),
            'total_gold_earned' => $this->when(isset($this->total_gold_earned), $this->total_gold_earned ?? 0),
            'total_gold_spent' => $this->when(isset($this->total_gold_spent), $this->total_gold_spent ?? 0),
        ];
    }
}
