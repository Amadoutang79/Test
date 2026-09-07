<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CauseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'goal_amount' => (float) $this->goal_amount,
            'is_active' => (bool) $this->is_active,
            'is_featured' => (bool) $this->is_featured,
            'total_pledged' => (float) ($this->pledges_sum_amount ?? 0),
            'pledge_count' => (int) ($this->pledges_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
