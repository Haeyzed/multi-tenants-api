<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductQuestion
 */
class ProductQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_id' => $this->customer_id,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'question' => $this->question,
            'answer' => $this->answer,
            'is_visible' => $this->is_visible,
            'is_answered' => $this->is_answered,
            'helpful_count' => $this->helpful_count,
            'answered_by' => $this->answered_by,
            'answered_at' => $this->answered_at?->toIso8601String(),
            'answered_by_user' => $this->when(
                $this->relationLoaded('answeredBy') && $this->answeredBy,
                fn () => [
                    'id' => $this->answeredBy->id,
                    'name' => $this->answeredBy->name,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
