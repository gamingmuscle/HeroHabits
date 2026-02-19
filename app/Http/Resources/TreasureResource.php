<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreasureResource extends JsonResource
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
            'gold_cost' => $this->gold_cost,
            'is_available' => (bool) $this->is_available,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Conditional attributes
            'can_afford' => $this->when(isset($this->can_afford), $this->can_afford ?? false),
            'times_purchased' => $this->when(isset($this->times_purchased), $this->times_purchased ?? 0),
        ];
    }
}
