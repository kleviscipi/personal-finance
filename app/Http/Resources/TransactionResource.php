<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'created_by' => $this->created_by,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'date' => $this->date,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'description' => $this->description,
            'payment_method' => $this->payment_method,
            'metadata' => $this->metadata,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
