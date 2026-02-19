<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'gold_reward' => $this->gold_reward,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Conditional attributes
            'pending_count' => $this->when(isset($this->pending_count), $this->pending_count ?? 0),
            'total_completions' => $this->when(isset($this->total_completions), $this->total_completions ?? 0),
            'accepted_count' => $this->when(isset($this->accepted_count), $this->accepted_count ?? 0),

            // Relationships
            'child' => $this->whenLoaded('child', function () {
                return new ChildResource($this->child);
            }),
        ];
    }
}
