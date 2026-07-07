<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductReview
 */
class ProductReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'images' => $this->images,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_approved' => $this->is_approved,
            'helpful_count' => $this->helpful_count,
            'unhelpful_count' => $this->unhelpful_count,
            'parent_id' => $this->parent_id,
            'admin_reply' => $this->admin_reply,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'has_reply' => $this->has_reply,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
