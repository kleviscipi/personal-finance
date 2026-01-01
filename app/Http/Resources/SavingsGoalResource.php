<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalResource extends JsonResource
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
            'account_id' => $this->account_id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->name,
            'target_amount' => $this->target_amount,
            'initial_amount' => $this->initial_amount,
            'currency' => $this->currency,
            'tracking_mode' => $this->tracking_mode,
            'start_date' => $this->start_date,
            'target_date' => $this->target_date,
            'settings' => $this->settings,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'user' => new UserResource($this->whenLoaded('user')),
            'progress' => $this->progress ?? null,
            'projection' => $this->projection ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
